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
        $financiamientos = Financiamiento::with(['admin', 'inversores', 'movimientos.inversor'])
            ->orderByRaw("estatus = 'Activo' desc")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Financiamiento $f) {
                $rendimientos = $f->movimientos->where('tipo', 'rendimiento');
                $activos      = $f->inversoresActivos();

                $f->stats = [
                    'aportado_activo'       => (float) $activos->sum('aporte'),
                    'rendimientos_cobrados' => (float) $rendimientos->sum('monto'),
                    'total_reinvertido'     => (float) $rendimientos->sum('monto_reinversion'),
                    'total_retornado'       => (float) $rendimientos->sum('monto_retornado'),
                    'total_retiros_owner'   => (float) $f->movimientos->where('tipo', 'retiro')->sum('monto'),
                    'n_rendimientos'        => $rendimientos->count(),
                    'n_inversores_activos'  => $activos->count(),
                ];

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

        $admins = User::where('puesto', 'admin')->where('activo', true)->orderBy('usuario')->get();

        // Tutorial de primera vez: solo si este usuario no lo ha visto/saltado
        $mostrarTour = !in_array('financiamientos', auth()->user()->tours_vistos ?? []);

        return view('owner.financiamientos', compact('financiamientos', 'globales', 'admins', 'mostrarTour'));
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
            // Primer inversor externo (opcional)
            'inv_nombre'      => 'nullable|string|max:120|required_with:inv_aporte',
            'inv_aporte'      => 'nullable|numeric|min:0.01',
            'inv_pct'         => 'nullable|numeric|min:0|max:100',
        ]);

        User::where('id', $data['admin_id'])->where('puesto', 'admin')->firstOrFail();

        $ownerAporte = round((float) $data['owner_aporte'], 2);
        $ownerPct    = round((float) ($data['owner_pct'] ?? 0), 2);
        $invAporte   = round((float) ($data['inv_aporte'] ?? 0), 2);
        $invPct      = round((float) ($data['inv_pct'] ?? 0), 2);
        $capital     = round($ownerAporte + $invAporte, 2);

        if ($capital <= 0) {
            return back()->withErrors(['owner_aporte' => 'La inversión inicial debe ser mayor a $0.'])->withInput();
        }
        // Validación en dinero: los retornos fijos mensuales (cada % es sobre el
        // aporte propio) no pueden exceder el rendimiento mensual de la cuenta.
        // Tasa mensual equivalente de una cuenta semanal = tasa × 4.
        $tasaMensual  = (float) $data['rendimiento_pct'] * ($data['frecuencia'] === 'semanal' ? 4 : 1);
        $fijosMes     = round($ownerAporte * $ownerPct / 100 + $invAporte * $invPct / 100, 2);
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
        $fijosMes       = round((float) $f->inversoresActivos()->sum(fn($i) => $i->aporte * $i->pct_retorno / 100), 2);
        $rendimientoMes = round($f->capital_actual * $tasaMensual / 100, 2);
        if ($fijosMes > $rendimientoMes) {
            return back()->withErrors([
                'rendimiento_pct' => 'Con esa tasa, el rendimiento mensual ($' . number_format($rendimientoMes, 2) . ') no alcanzaría para los retornos fijos activos ($' . number_format($fijosMes, 2) . ').',
            ])->with('open_financiamiento', $f->id);
        }

        $f->update($data);

        return redirect()->route('owner.financiamientos.index')
            ->with('success', 'Acuerdo actualizado.')
            ->with('open_financiamiento', $f->id);
    }

    /**
     * Agregar un inversor a la cuenta (su aporte suma al capital).
     */
    public function storeInversor(Request $request, int $id)
    {
        $f = Financiamiento::with('inversores')->findOrFail($id);

        $data = $request->validate([
            'nombre'        => 'required|string|max:120',
            'aporte'        => 'required|numeric|min:0.01',
            'pct_retorno'   => 'required|numeric|min:0|max:100',
            'fecha_ingreso' => 'required|date',
            'es_owner'      => 'nullable|boolean',
        ]);

        // Misma lógica que al crear la cuenta: cada % es mensual sobre el aporte
        // propio. Se valida en dinero, considerando que el nuevo aporte también
        // suma capital (y por tanto rendimiento).
        $tasaMensual    = $f->rendimiento_pct * $f->periodos_por_mes;
        $nuevoAporte    = round((float) $data['aporte'], 2);
        $fijosMes       = round((float) $f->inversoresActivos()->sum(fn($i) => $i->aporte * $i->pct_retorno / 100)
                        + $nuevoAporte * (float) $data['pct_retorno'] / 100, 2);
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
            'aporte'            => round((float) $data['aporte'], 2),
            'pct_retorno'       => $data['pct_retorno'],
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

        return redirect()->route('owner.financiamientos.index')
            ->with('success', "Inversor \"{$inv->nombre}\" agregado.")
            ->with('open_financiamiento', $f->id);
    }

    /**
     * Modificar un inversor (el % de retorno es ajustable en cualquier momento).
     */
    public function updateInversor(Request $request, int $id, FinanciamientoInversor $inversor)
    {
        abort_if($inversor->financiamiento_id !== $id, 404);
        $f = $inversor->financiamiento;

        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'pct_retorno' => 'required|numeric|min:0|max:100',
        ]);

        if ($inversor->estatus === 'Activo') {
            $tasaMensual    = $f->rendimiento_pct * $f->periodos_por_mes;
            $fijosMes       = round((float) $f->inversoresActivos()->where('id', '!=', $inversor->id)->sum(fn($i) => $i->aporte * $i->pct_retorno / 100)
                            + $inversor->aporte * (float) $data['pct_retorno'] / 100, 2);
            $rendimientoMes = round($f->capital_actual * $tasaMensual / 100, 2);
            if ($fijosMes > $rendimientoMes) {
                return back()->withErrors([
                    'pct_retorno' => 'Los retornos fijos mensuales ($' . number_format($fijosMes, 2) . ') excederían el rendimiento mensual de la cuenta ($' . number_format($rendimientoMes, 2) . ').',
                ])->with('open_financiamiento', $f->id);
            }
        }

        $inversor->update($data);

        return redirect()->route('owner.financiamientos.index')
            ->with('success', "Inversor \"{$inversor->nombre}\" actualizado.")
            ->with('open_financiamiento', $f->id);
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

            $inversor->update(['estatus' => 'Transferido', 'fecha_salida' => $hoy->toDateString(), 'pct_retorno' => 0]);

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

            $inversor->update(['estatus' => 'Retirado', 'fecha_salida' => $hoy->toDateString(), 'pct_retorno' => 0]);

            $msg = "Se devolvió el aporte a \"{$inversor->nombre}\"; su % de retorno pasa a reinversión.";
        }

        return redirect()->route('owner.financiamientos.index')
            ->with('success', $msg)
            ->with('open_financiamiento', $f->id);
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
            'fecha'       => 'required|date',
            'nota'        => 'nullable|string|max:255',
            'capitalizar' => 'nullable|boolean',
        ]);

        $monto            = round((float) $data['monto'], 2);
        $montoReinversion = 0.0;
        $montoRetornado   = 0.0;
        $capitalizado     = false;
        $detalle          = null;

        if ($data['tipo'] === 'rendimiento') {
            // Retorno FIJO por inversor: su propio aporte × su % (no depende del
            // capital total ni crece con la reinversión). Lo que sobra del monto
            // cobrado se reinvierte. Si el cobro no alcanza para los retornos
            // fijos (cobro parcial), se reparten proporcionalmente.
            $detalle = [];
            $fijos   = [];
            $totalFijo = 0.0;

            foreach ($f->inversoresActivos() as $inv) {
                if ($inv->pct_retorno <= 0) continue;
                // pct_retorno es mensual: en cuentas semanales se prorratea entre 4 semanas
                $fijo = round($inv->aporte * $inv->pct_retorno / 100 / $f->periodos_por_mes, 2);
                if ($fijo <= 0) continue;
                $fijos[] = ['inv' => $inv, 'fijo' => $fijo];
                $totalFijo += $fijo;
            }

            $escala = ($totalFijo > 0 && $totalFijo > $monto) ? $monto / $totalFijo : 1.0;

            foreach ($fijos as $fila) {
                $retorno = round($fila['fijo'] * $escala, 2);
                $montoRetornado += $retorno;
                $detalle[] = [
                    'inversor_id' => $fila['inv']->id,
                    'nombre'      => $fila['inv']->nombre,
                    'pct'         => (float) $fila['inv']->pct_retorno,
                    'monto'       => $retorno,
                ];
            }

            $montoRetornado   = round($montoRetornado, 2);
            $montoReinversion = round(max(0, $monto - $montoRetornado), 2);
            $capitalizado     = $request->boolean('capitalizar');

            if ($capitalizado && $montoReinversion > 0) {
                $f->capital_actual = round($f->capital_actual + $montoReinversion, 2);
                $f->save();
            }
        } elseif ($data['tipo'] === 'aporte') {
            $f->capital_actual = round($f->capital_actual + $monto, 2);
            $f->save();
        } else { // retiro (owner / Derian)
            if ($monto > $f->capital_actual) {
                return back()->withErrors([
                    'monto' => 'El retiro no puede exceder el capital vigente ($' . number_format($f->capital_actual, 2) . ').',
                ])->with('open_financiamiento', $f->id);
            }
            $f->capital_actual = round($f->capital_actual - $monto, 2);
            $f->save();
        }

        FinanciamientoMovimiento::create([
            'financiamiento_id' => $f->id,
            'tipo'              => $data['tipo'],
            'monto'             => $monto,
            'monto_reinversion' => $montoReinversion,
            'monto_retornado'   => $montoRetornado,
            'capitalizado'      => $capitalizado,
            'detalle'           => $detalle,
            'fecha'             => $data['fecha'],
            'nota'              => $data['nota'] ?? null,
        ]);

        $labels = [
            'rendimiento' => 'Rendimiento registrado',
            'aporte'      => 'Aporte de capital registrado',
            'retiro'      => 'Retiro registrado',
        ];

        return redirect()->route('owner.financiamientos.index')
            ->with('success', $labels[$data['tipo']] . '.')
            ->with('open_financiamiento', $f->id);
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

        return redirect()->route('owner.financiamientos.index')
            ->with('success', 'Movimiento eliminado y capital ajustado.')
            ->with('open_financiamiento', $f->id);
    }

    /**
     * Alternar estatus Activo / Finalizado.
     */
    public function toggle(int $id)
    {
        $f = Financiamiento::findOrFail($id);
        $f->estatus = $f->estatus === 'Activo' ? 'Finalizado' : 'Activo';
        $f->save();

        return redirect()->route('owner.financiamientos.index')
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
