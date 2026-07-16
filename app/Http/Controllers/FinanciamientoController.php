<?php

namespace App\Http\Controllers;

use App\Models\Financiamiento;
use App\Models\FinanciamientoInversor;
use App\Models\FinanciamientoMovimiento;
use App\Models\User;
use Illuminate\Http\Request;

class FinanciamientoController extends Controller
{
    /**
     * Vista principal: cuentas de inversión conjunta hacia administradores.
     */
    public function index()
    {
        $financiamientos = Financiamiento::with(['admin', 'inversores', 'movimientos.inversor', 'movimientos.registradoPor'])
            ->orderByRaw("estatus = 'Activo' desc")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Financiamiento $f) {
                $f->stats = $f->statsResumen();

                return $f;
            });

        $activosF = $financiamientos->where('estatus', 'Activo');
        $globales = [
            'capital_vigente'    => (float) $activosF->sum('capital_actual'),
            'rendimiento_semana' => (float) $activosF->where('frecuencia', 'semanal')->sum(fn($f) => $f->rendimiento_periodo),
            'rendimiento_mes'    => (float) $activosF->where('frecuencia', 'mensual')->sum(fn($f) => $f->rendimiento_periodo),
            'total_generado'     => (float) $financiamientos->sum(fn($f) => $f->stats['rendimientos_cobrados']),
            'total_reinvertido'  => (float) $financiamientos->sum(fn($f) => $f->stats['total_reinvertido']),
            'total_retornado'    => (float) $financiamientos->sum(fn($f) => $f->stats['total_retornado']),
            'activos'            => $activosF->count(),
        ];

        // Solo admins reales: los sombra (carteras financiadas) no se financian
        $admins = User::where('puesto', 'admin')->whereNull('cartera_financiada_de')->where('activo', true)->orderBy('usuario')->get();

        // Tutorial de primera vez: solo si este usuario no lo ha visto/saltado
        $mostrarTour = !in_array('financiamientos', auth()->user()->tours_vistos ?? []);

        return view('owner.financiamientos', compact('financiamientos', 'globales', 'admins', 'mostrarTour'));
    }

    /**
     * Detalle de una cuenta de inversión: KPIs de cumplimiento, gráfica
     * teórico vs real con fechas reales de cobro, evolución del capital,
     * comparativa por periodo, inversores e historial.
     */
    public function show(int $id)
    {
        $f = Financiamiento::with(['admin', 'inversores', 'movimientos.inversor', 'movimientos.registradoPor'])
            ->findOrFail($id);

        $stats    = $f->statsResumen();
        $serie    = $f->serieComparativa();
        $capital  = $f->serieCapital();
        $periodos = $f->resumenPeriodos();
        $estado   = $f->estadoCobros();

        // Fijos pendientes de la ventana de hoy, para el preview del reparto
        $pagadosHoy   = $f->retornosPagadosPeriodo($f->periodoDeFecha(now()->startOfDay()));
        $inversoresJs = $f->inversoresActivos()
            ->filter(fn($i) => $i->retorno_mensual > 0)
            ->map(fn($i) => [
                'nombre' => $i->nombre,
                'ret'    => (float) $i->retorno_mensual,
                'pend'   => round(max(0, $i->retorno_mensual / $f->periodos_por_mes - ($pagadosHoy[$i->id] ?? 0)), 2),
            ])->values()->all();

        return view('owner.financiamiento_detalle', compact('f', 'stats', 'serie', 'capital', 'periodos', 'estado', 'inversoresJs'));
    }

    /**
     * Redirige de vuelta a la página desde la que se hizo la acción: el
     * detalle del financiamiento si se venía de ahí, o el listado.
     */
    private function volver(int $id)
    {
        $prevPath = parse_url(url()->previous(), PHP_URL_PATH) ?? '';
        if (preg_match('~/financiamientos/' . $id . '$~', $prevPath)) {
            return redirect()->route('owner.financiamientos.show', $id);
        }

        return redirect()->route('owner.financiamientos.index')->with('open_financiamiento', $id);
    }

    /**
     * Marcar el tutorial de esta vista como visto (al terminarlo o saltarlo).
     */
    public function tourVisto()
    {
        $user  = auth()->user();
        $tours = $user->tours_vistos ?? [];
        if (!in_array('financiamientos', $tours)) {
            $tours[] = 'financiamientos';
            $user->tours_vistos = $tours;
            $user->save();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Normaliza el retorno de un inversor: el acuerdo puede capturarse como
     * monto fijo mensual ($) o como % mensual del aporte. El monto es el dato
     * canónico; el % se guarda como equivalencia informativa.
     *
     * @return array{0: float, 1: float} [retorno_mensual, pct_equivalente]
     */
    private function normalizarRetorno(float $aporte, $pct, $monto): array
    {
        $retorno = ($monto !== null && $monto !== '')
            ? round((float) $monto, 2)
            : round($aporte * (float) ($pct ?? 0) / 100, 2);

        $pctEq = $aporte > 0 ? round($retorno / $aporte * 100, 2) : 0.0;

        return [$retorno, $pctEq];
    }

    /**
     * Crear la cuenta de inversión: acuerdo con el admin + inversores iniciales.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'admin_id'        => 'required|exists:users,id',
            'rendimiento_pct' => 'required|numeric|min:0.01|max:100',
            'frecuencia'      => 'required|in:semanal,mensual',
            'plazo_meses'     => 'required|integer|min:1|max:120',
            'fecha_inicio'    => 'required|date',
            'notas'           => 'nullable|string|max:2000',
            // Participación del owner (Derian)
            'owner_nombre'    => 'required|string|max:120',
            'owner_aporte'    => 'required|numeric|min:0',
            'owner_pct'       => 'nullable|numeric|min:0|max:100',
            'owner_retorno'   => 'nullable|numeric|min:0',
            // Primer inversor externo (opcional)
            'inv_nombre'      => 'nullable|string|max:120|required_with:inv_aporte',
            'inv_aporte'      => 'nullable|numeric|min:0.01',
            'inv_pct'         => 'nullable|numeric|min:0|max:100',
            'inv_retorno'     => 'nullable|numeric|min:0',
        ]);

        User::where('id', $data['admin_id'])->where('puesto', 'admin')->whereNull('cartera_financiada_de')->firstOrFail();

        $ownerAporte = round((float) $data['owner_aporte'], 2);
        $invAporte   = round((float) ($data['inv_aporte'] ?? 0), 2);
        [$ownerRet, $ownerPct] = $this->normalizarRetorno($ownerAporte, $data['owner_pct'] ?? null, $data['owner_retorno'] ?? null);
        [$invRet, $invPct]     = $this->normalizarRetorno($invAporte, $data['inv_pct'] ?? null, $data['inv_retorno'] ?? null);
        $capital     = round($ownerAporte + $invAporte, 2);

        if ($capital <= 0) {
            return back()->withErrors(['owner_aporte' => 'La inversión inicial debe ser mayor a $0.'])->withInput();
        }
        // Validación en dinero: los retornos fijos mensuales no pueden exceder
        // el rendimiento mensual de la cuenta.
        // Tasa mensual equivalente de una cuenta semanal = tasa × 4.
        $tasaMensual  = (float) $data['rendimiento_pct'] * ($data['frecuencia'] === 'semanal' ? 4 : 1);
        $fijosMes     = round($ownerRet + $invRet, 2);
        $rendimientoMes = round($capital * $tasaMensual / 100, 2);
        if ($fijosMes > $rendimientoMes) {
            return back()->withErrors([
                'inv_pct' => 'Los retornos fijos mensuales ($' . number_format($fijosMes, 2) . ') excederían el rendimiento mensual de la cuenta ($' . number_format($rendimientoMes, 2) . ').',
            ])->withInput();
        }

        $f = Financiamiento::create([
            'admin_id'        => $data['admin_id'],
            'capital_actual'  => $capital,
            'rendimiento_pct' => $data['rendimiento_pct'],
            'frecuencia'      => $data['frecuencia'],
            'plazo_meses'     => $data['plazo_meses'],
            'fecha_inicio'    => $data['fecha_inicio'],
            'estatus'         => 'Activo',
            'notas'           => $data['notas'] ?? null,
        ]);

        if ($ownerAporte > 0) {
            $owner = FinanciamientoInversor::create([
                'financiamiento_id' => $f->id,
                'nombre'            => $data['owner_nombre'],
                'es_owner'          => true,
                'aporte'            => $ownerAporte,
                'pct_retorno'       => $ownerPct,
                'retorno_mensual'   => $ownerRet,
                'fecha_ingreso'     => $data['fecha_inicio'],
                'fecha_limite'      => null, // el owner no tiene convenio de salida
            ]);
            FinanciamientoMovimiento::create([
                'financiamiento_id' => $f->id,
                'inversor_id'       => $owner->id,
                'tipo'              => 'aporte',
                'monto'             => $ownerAporte,
                'fecha'             => $data['fecha_inicio'],
                'nota'              => 'Aporte inicial (owner)',
            ]);
        }

        if ($invAporte > 0) {
            $inv = FinanciamientoInversor::create([
                'financiamiento_id' => $f->id,
                'nombre'            => $data['inv_nombre'],
                'es_owner'          => false,
                'aporte'            => $invAporte,
                'pct_retorno'       => $invPct,
                'retorno_mensual'   => $invRet,
                'fecha_ingreso'     => $data['fecha_inicio'],
                'fecha_limite'      => \Carbon\Carbon::parse($data['fecha_inicio'])->addMonths((int) $data['plazo_meses'])->toDateString(),
            ]);
            FinanciamientoMovimiento::create([
                'financiamiento_id' => $f->id,
                'inversor_id'       => $inv->id,
                'tipo'              => 'aporte',
                'monto'             => $invAporte,
                'fecha'             => $data['fecha_inicio'],
                'nota'              => 'Aporte inicial',
            ]);
        }

        return redirect()->route('owner.financiamientos.index')
            ->with('success', 'Cuenta de inversión creada correctamente.');
    }

    /**
     * Actualizar los términos del acuerdo.
     */
    public function update(Request $request, int $id)
    {
        $f = Financiamiento::with('inversores')->findOrFail($id);

        $data = $request->validate([
            'rendimiento_pct' => 'required|numeric|min:0.01|max:100',
            'frecuencia'      => 'required|in:semanal,mensual',
            'plazo_meses'     => 'required|integer|min:1|max:120',
            'fecha_inicio'    => 'required|date',
            'notas'           => 'nullable|string|max:2000',
        ]);

        // Con la nueva tasa/frecuencia, el rendimiento mensual debe alcanzar
        // para los retornos fijos mensuales de los inversores activos.
        $tasaMensual    = (float) $data['rendimiento_pct'] * ($data['frecuencia'] === 'semanal' ? 4 : 1);
        $fijosMes       = $f->fijos_mensuales;
        $rendimientoMes = round($f->capital_actual * $tasaMensual / 100, 2);
        if ($fijosMes > $rendimientoMes) {
            return back()->withErrors([
                'rendimiento_pct' => 'Con esa tasa, el rendimiento mensual ($' . number_format($rendimientoMes, 2) . ') no alcanzaría para los retornos fijos activos ($' . number_format($fijosMes, 2) . ').',
            ])->with('open_financiamiento', $f->id);
        }

        // Si cambia la fecha de inicio, mover con ella los aportes iniciales
        // (los fechados el día del inicio anterior): el calendario de cobros y
        // la reconstrucción del capital por periodo se anclan a esa fecha.
        $inicioAnterior = $f->fecha_inicio->toDateString();
        $f->update($data);
        if ($data['fecha_inicio'] !== $inicioAnterior) {
            $f->movimientos()->where('tipo', 'aporte')->whereDate('fecha', $inicioAnterior)
                ->update(['fecha' => $data['fecha_inicio']]);
            foreach ($f->inversores()->whereDate('fecha_ingreso', $inicioAnterior)->get() as $inv) {
                $inv->update([
                    'fecha_ingreso' => $data['fecha_inicio'],
                    'fecha_limite'  => $inv->es_owner ? null
                        : \Carbon\Carbon::parse($data['fecha_inicio'])->addMonths((int) $data['plazo_meses'])->toDateString(),
                ]);
            }
        }

        return $this->volver($f->id)->with('success', 'Acuerdo actualizado.');
    }

    /**
     * Agregar un inversor a la cuenta (su aporte suma al capital).
     */
    public function storeInversor(Request $request, int $id)
    {
        $f = Financiamiento::with('inversores')->findOrFail($id);

        $data = $request->validate([
            'nombre'          => 'required|string|max:120',
            'aporte'          => 'required|numeric|min:0.01',
            'pct_retorno'     => 'nullable|numeric|min:0|max:100|required_without:retorno_mensual',
            'retorno_mensual' => 'nullable|numeric|min:0',
            'fecha_ingreso'   => 'required|date',
            'es_owner'        => 'nullable|boolean',
        ]);

        // El retorno fijo se captura como monto $/mes o como % mensual del
        // aporte propio. Se valida en dinero, considerando que el nuevo aporte
        // también suma capital (y por tanto rendimiento).
        $tasaMensual    = $f->rendimiento_pct * $f->periodos_por_mes;
        $nuevoAporte    = round((float) $data['aporte'], 2);
        [$nuevoRet, $nuevoPct] = $this->normalizarRetorno($nuevoAporte, $data['pct_retorno'] ?? null, $data['retorno_mensual'] ?? null);
        $fijosMes       = round($f->fijos_mensuales + $nuevoRet, 2);
        $rendimientoMes = round(($f->capital_actual + $nuevoAporte) * $tasaMensual / 100, 2);
        if ($fijosMes > $rendimientoMes) {
            return back()->withErrors([
                'pct_retorno' => 'Los retornos fijos mensuales ($' . number_format($fijosMes, 2) . ') excederían el rendimiento mensual de la cuenta ($' . number_format($rendimientoMes, 2) . ').',
            ])->with('open_financiamiento', $f->id);
        }

        $esOwner = $request->boolean('es_owner');

        $inv = FinanciamientoInversor::create([
            'financiamiento_id' => $f->id,
            'nombre'            => $data['nombre'],
            'es_owner'          => $esOwner,
            'aporte'            => $nuevoAporte,
            'pct_retorno'       => $nuevoPct,
            'retorno_mensual'   => $nuevoRet,
            'fecha_ingreso'     => $data['fecha_ingreso'],
            'fecha_limite'      => $esOwner ? null
                : \Carbon\Carbon::parse($data['fecha_ingreso'])->addMonths((int) $f->plazo_meses)->toDateString(),
        ]);

        $f->capital_actual = round($f->capital_actual + $inv->aporte, 2);
        $f->save();

        FinanciamientoMovimiento::create([
            'financiamiento_id' => $f->id,
            'inversor_id'       => $inv->id,
            'tipo'              => 'aporte',
            'monto'             => $inv->aporte,
            'fecha'             => $data['fecha_ingreso'],
            'nota'              => 'Aporte de ' . $inv->nombre,
        ]);

        return $this->volver($f->id)->with('success', "Inversor \"{$inv->nombre}\" agregado.");
    }

    /**
     * Modificar un inversor (el % de retorno es ajustable en cualquier momento).
     */
    public function updateInversor(Request $request, int $id, FinanciamientoInversor $inversor)
    {
        abort_if($inversor->financiamiento_id !== $id, 404);
        $f = $inversor->financiamiento;

        $data = $request->validate([
            'nombre'          => 'required|string|max:120',
            'pct_retorno'     => 'nullable|numeric|min:0|max:100|required_without:retorno_mensual',
            'retorno_mensual' => 'nullable|numeric|min:0',
        ]);

        [$nuevoRet, $nuevoPct] = $this->normalizarRetorno((float) $inversor->aporte, $data['pct_retorno'] ?? null, $data['retorno_mensual'] ?? null);

        if ($inversor->estatus === 'Activo') {
            $tasaMensual    = $f->rendimiento_pct * $f->periodos_por_mes;
            $fijosMes       = round((float) $f->inversoresActivos()->where('id', '!=', $inversor->id)->sum('retorno_mensual') + $nuevoRet, 2);
            $rendimientoMes = round($f->capital_actual * $tasaMensual / 100, 2);
            if ($fijosMes > $rendimientoMes) {
                return back()->withErrors([
                    'pct_retorno' => 'Los retornos fijos mensuales ($' . number_format($fijosMes, 2) . ') excederían el rendimiento mensual de la cuenta ($' . number_format($rendimientoMes, 2) . ').',
                ])->with('open_financiamiento', $f->id);
            }
        }

        $inversor->update([
            'nombre'          => $data['nombre'],
            'pct_retorno'     => $nuevoPct,
            'retorno_mensual' => $nuevoRet,
        ]);

        return $this->volver($f->id)->with('success', "Inversor \"{$inversor->nombre}\" actualizado.");
    }

    /**
     * Salida de un inversor del convenio:
     *  - Dentro del plazo: se le devuelve su aporte (sale del capital).
     *  - Vencido el plazo: pierde el derecho y su aporte se transfiere al owner
     *    (el capital total no cambia).
     */
    public function salidaInversor(int $id, FinanciamientoInversor $inversor)
    {
        abort_if($inversor->financiamiento_id !== $id, 404);
        $f = $inversor->financiamiento;

        if ($inversor->estatus !== 'Activo') {
            return back()->withErrors(['salida' => 'Este inversor ya no está activo.'])
                ->with('open_financiamiento', $f->id);
        }
        if ($inversor->es_owner) {
            return back()->withErrors(['salida' => 'La participación del owner no se retira; usa "Retiro" en movimientos de capital.'])
                ->with('open_financiamiento', $f->id);
        }

        $hoy = now()->startOfDay();

        if ($inversor->convenio_vencido) {
            // Venció el convenio: el aporte pasa a la participación del owner.
            $owner = $f->inversoresActivos()->firstWhere('es_owner', true);
            if ($owner) {
                $owner->aporte = round($owner->aporte + $inversor->aporte, 2);
                $owner->save();
            } else {
                FinanciamientoInversor::create([
                    'financiamiento_id' => $f->id,
                    'nombre'            => 'Owner',
                    'es_owner'          => true,
                    'aporte'            => $inversor->aporte,
                    'pct_retorno'       => 0,
                    'fecha_ingreso'     => $hoy->toDateString(),
                    'fecha_limite'      => null,
                ]);
            }

            FinanciamientoMovimiento::create([
                'financiamiento_id' => $f->id,
                'inversor_id'       => $inversor->id,
                'tipo'              => 'transferencia_owner',
                'monto'             => $inversor->aporte,
                'fecha'             => $hoy->toDateString(),
                'nota'              => "Convenio vencido: capital de {$inversor->nombre} transferido al owner",
            ]);

            $inversor->update(['estatus' => 'Transferido', 'fecha_salida' => $hoy->toDateString(), 'pct_retorno' => 0, 'retorno_mensual' => 0]);

            $msg = "Convenio vencido: el capital de \"{$inversor->nombre}\" se transfirió al owner (el total invertido no cambia).";
        } else {
            // Dentro del plazo: devolver su aporte.
            if ($inversor->aporte > $f->capital_actual) {
                return back()->withErrors([
                    'salida' => 'El capital vigente ($' . number_format($f->capital_actual, 2) . ') no alcanza para devolver el aporte.',
                ])->with('open_financiamiento', $f->id);
            }

            $f->capital_actual = round($f->capital_actual - $inversor->aporte, 2);
            $f->save();

            FinanciamientoMovimiento::create([
                'financiamiento_id' => $f->id,
                'inversor_id'       => $inversor->id,
                'tipo'              => 'salida_inversor',
                'monto'             => $inversor->aporte,
                'fecha'             => $hoy->toDateString(),
                'nota'              => "Devolución de capital a {$inversor->nombre}",
            ]);

            $inversor->update(['estatus' => 'Retirado', 'fecha_salida' => $hoy->toDateString(), 'pct_retorno' => 0, 'retorno_mensual' => 0]);

            $msg = "Se devolvió el aporte a \"{$inversor->nombre}\"; su % de retorno pasa a reinversión.";
        }

        return $this->volver($f->id)->with('success', $msg);
    }

    /**
     * Registrar un movimiento: rendimiento del periodo, aporte o retiro del owner.
     */
    public function storeMovimiento(Request $request, int $id)
    {
        $f = Financiamiento::with('inversores')->findOrFail($id);

        $data = $request->validate([
            'tipo'        => 'required|in:rendimiento,aporte,retiro',
            'monto'       => 'required|numeric|min:0.01',
            // Cobros, aportes y retiros son hechos reales: nunca a futuro.
            'fecha'       => 'required|date|before_or_equal:today',
            'nota'        => 'nullable|string|max:255',
            'capitalizar' => 'nullable|boolean',
        ], [
            'fecha.before_or_equal' => 'La fecha del movimiento no puede ser futura.',
        ]);

        $monto = round((float) $data['monto'], 2);

        if ($data['tipo'] === 'rendimiento') {
            $f->registrarRendimiento($monto, $data['fecha'], $data['nota'] ?? null, $request->boolean('capitalizar'), auth()->user()->id);
        } else {
            if ($data['tipo'] === 'aporte') {
                $f->capital_actual = round($f->capital_actual + $monto, 2);
            } else { // retiro (owner / Derian)
                if ($monto > $f->capital_actual) {
                    return back()->withErrors([
                        'monto' => 'El retiro no puede exceder el capital vigente ($' . number_format($f->capital_actual, 2) . ').',
                    ])->with('open_financiamiento', $f->id);
                }
                $f->capital_actual = round($f->capital_actual - $monto, 2);
            }
            $f->save();

            FinanciamientoMovimiento::create([
                'financiamiento_id' => $f->id,
                'tipo'              => $data['tipo'],
                'monto'             => $monto,
                'fecha'             => $data['fecha'],
                'nota'              => $data['nota'] ?? null,
                'registrado_por'    => auth()->user()->id,
            ]);
        }

        $labels = [
            'rendimiento' => 'Rendimiento registrado',
            'aporte'      => 'Aporte de capital registrado',
            'retiro'      => 'Retiro registrado',
        ];

        return $this->volver($f->id)->with('success', $labels[$data['tipo']] . '.');
    }

    /**
     * Eliminar un movimiento revirtiendo su efecto sobre el capital.
     * Los movimientos ligados a inversores (aportes, salidas, transferencias)
     * no se eliminan para no romper la trazabilidad.
     */
    public function destroyMovimiento(int $id, FinanciamientoMovimiento $movimiento)
    {
        abort_if($movimiento->financiamiento_id !== $id, 404);

        if ($movimiento->inversor_id !== null || in_array($movimiento->tipo, ['salida_inversor', 'transferencia_owner'])) {
            return back()->withErrors([
                'movimiento' => 'Los movimientos ligados a un inversor no se pueden eliminar.',
            ])->with('open_financiamiento', $id);
        }

        $f = $movimiento->financiamiento;

        if ($movimiento->tipo === 'rendimiento' && $movimiento->capitalizado) {
            $f->capital_actual = round($f->capital_actual - $movimiento->monto_reinversion, 2);
        } elseif ($movimiento->tipo === 'aporte') {
            $f->capital_actual = round($f->capital_actual - $movimiento->monto, 2);
        } elseif ($movimiento->tipo === 'retiro') {
            $f->capital_actual = round($f->capital_actual + $movimiento->monto, 2);
        }
        $f->capital_actual = max(0, $f->capital_actual);
        $f->save();

        $movimiento->delete();

        return $this->volver($f->id)->with('success', 'Movimiento eliminado y capital ajustado.');
    }

    /**
     * Alternar estatus Activo / Finalizado.
     */
    public function toggle(int $id)
    {
        $f = Financiamiento::findOrFail($id);
        $f->estatus = $f->estatus === 'Activo' ? 'Finalizado' : 'Activo';
        $f->save();

        return $this->volver($f->id)
            ->with('success', $f->estatus === 'Activo' ? 'Cuenta de inversión reactivada.' : 'Cuenta de inversión finalizada.');
    }

    /**
     * Eliminar una cuenta de inversión y su historial.
     */
    public function destroy(int $id)
    {
        Financiamiento::findOrFail($id)->delete();

        return redirect()->route('owner.financiamientos.index')
            ->with('success', 'Cuenta de inversión eliminada.');
    }
}
