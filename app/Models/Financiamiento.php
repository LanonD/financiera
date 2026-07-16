<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Financiamiento extends Model
{
    protected $table = 'financiamientos';

    protected $fillable = [
        'admin_id', 'capital_actual', 'rendimiento_pct',
        'frecuencia', 'plazo_meses', 'fecha_inicio', 'estatus', 'notas',
    ];

    protected $casts = [
        'capital_actual'  => 'float',
        'rendimiento_pct' => 'float',
        'plazo_meses'     => 'integer',
        'fecha_inicio'    => 'date',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function inversores()
    {
        return $this->hasMany(FinanciamientoInversor::class, 'financiamiento_id')
            ->orderByDesc('es_owner')->orderBy('fecha_ingreso');
    }

    public function movimientos()
    {
        return $this->hasMany(FinanciamientoMovimiento::class, 'financiamiento_id')
            ->orderBy('fecha', 'desc')->orderBy('id', 'desc');
    }

    /** Inversores que siguen dentro del convenio. */
    public function inversoresActivos()
    {
        return $this->inversores->where('estatus', 'Activo');
    }

    /** Suma de % de retorno de los inversores activos. */
    public function getPctRetornoActivosAttribute(): float
    {
        return round((float) $this->inversoresActivos()->sum('pct_retorno'), 2);
    }

    /** Retornos mensuales fijos ($) comprometidos con los inversores activos. */
    public function getFijosMensualesAttribute(): float
    {
        return round((float) $this->inversoresActivos()->sum('retorno_mensual'), 2);
    }

    /** % que se reinvierte: lo que sobra de la tasa total tras pagar retornos. */
    public function getPctReinversionAttribute(): float
    {
        return round(max(0, $this->rendimiento_pct - $this->pct_retorno_activos), 2);
    }

    /** Rendimiento esperado del periodo (semana o mes) sobre el capital vigente. */
    public function getRendimientoPeriodoAttribute(): float
    {
        return round($this->capital_actual * $this->rendimiento_pct / 100, 2);
    }

    /** Etiqueta del periodo según la frecuencia. */
    public function getPeriodoLabelAttribute(): string
    {
        return $this->frecuencia === 'mensual' ? 'mes' : 'semana';
    }

    /**
     * Periodos que caben en un mes (el pct_retorno de los inversores es SIEMPRE
     * mensual; en cuentas semanales se prorratea entre 4 semanas).
     */
    public function getPeriodosPorMesAttribute(): int
    {
        return $this->frecuencia === 'semanal' ? 4 : 1;
    }

    /**
     * Fecha del n-ésimo cobro del calendario (n = 1, 2, …). El primer cobro
     * toca un periodo completo (semana o mes, según la frecuencia del acuerdo)
     * después del inicio del financiamiento.
     */
    public function fechaCobro(int $n): \Carbon\Carbon
    {
        $inicio = $this->fecha_inicio->copy()->startOfDay();

        return $this->frecuencia === 'semanal'
            ? $inicio->addWeeks($n)
            : $inicio->addMonthsNoOverflow($n);
    }

    /**
     * Ventana de cobro (1, 2, …) a la que pertenece una fecha: la ventana n
     * abarca desde el día siguiente a fechaCobro(n−1) hasta fechaCobro(n).
     * Fechas en o antes del inicio caen en la ventana 1.
     */
    public function periodoDeFecha(\Carbon\Carbon $fecha): int
    {
        $n = 1;
        while ($n < 2000 && $this->fechaCobro($n)->lt($fecha->copy()->startOfDay())) {
            $n++;
        }
        return $n;
    }

    /**
     * Capital que genera rendimiento en el periodo n, reconstruido del
     * historial: los aportes/retiros/salidas cuentan desde el periodo en cuya
     * ventana caen (capital que alcanzó a estar dentro en ese periodo); las
     * reinversiones capitalizadas surten efecto hasta el periodo SIGUIENTE,
     * porque se generan al cobrar, al final de su periodo.
     */
    public function capitalInicioPeriodo(int $n): float
    {
        $capital = 0.0;

        foreach ($this->movimientos as $m) {
            $ventana = $this->periodoDeFecha($m->fecha);

            if ($m->tipo === 'rendimiento') {
                if ($m->capitalizado && $ventana < $n) {
                    $capital += $m->monto_reinversion;
                }
            } elseif ($ventana <= $n) {
                if ($m->tipo === 'aporte') {
                    $capital += $m->monto;
                } elseif (in_array($m->tipo, ['retiro', 'salida_inversor'])) {
                    $capital -= $m->monto;
                }
                // transferencia_owner no cambia el capital total
            }
        }

        return round(max(0, $capital), 2);
    }

    /** Rendimiento esperado del periodo n: tasa sobre el capital a su inicio. */
    public function esperadoPeriodo(int $n): float
    {
        return round($this->capitalInicioPeriodo($n) * $this->rendimiento_pct / 100, 2);
    }

    /**
     * Retornos fijos ya pagados a cada inversor dentro de la ventana n
     * (suma del detalle de los rendimientos ya registrados en esa ventana).
     *
     * @return array<int, float> inversor_id => monto pagado
     */
    public function retornosPagadosPeriodo(int $n): array
    {
        $pagados = [];
        foreach ($this->movimientos->where('tipo', 'rendimiento') as $m) {
            if ($this->periodoDeFecha($m->fecha) !== $n) continue;
            foreach ($m->detalle ?? [] as $d) {
                $pagados[$d['inversor_id']] = round(($pagados[$d['inversor_id']] ?? 0) + $d['monto'], 2);
            }
        }
        return $pagados;
    }

    /**
     * Estado del calendario de cobros al día de hoy: ventanas cubiertas,
     * atrasos, cobro parcial en curso y el periodo pendiente con su esperado.
     * Lo comparten el supervisor (quien cobra) y el admin financiado (quien
     * paga) para que ambos vean exactamente lo mismo.
     */
    public function estadoCobros(?\Carbon\Carbon $hoy = null): object
    {
        $hoy   = ($hoy ?? now())->copy()->startOfDay();
        $rends = $this->movimientos->where('tipo', 'rendimiento');

        // Una ventana con al menos un cobro cuenta como cubierta (los cobros
        // parciales se completan dentro de la misma ventana).
        $cubiertas = $rends->map(fn($m) => $this->periodoDeFecha($m->fecha))->unique();

        $vencidos = 0;
        while ($vencidos < 520 && $this->fechaCobro($vencidos + 1)->lte($hoy)) {
            $vencidos++;
        }

        $atrasadasN = $vencidos > 0
            ? collect(range(1, $vencidos))->reject(fn($n) => $cubiertas->contains($n))
            : collect();

        // Ventana actual con cobros parciales (aún no llega al esperado)
        $nActual        = $this->periodoDeFecha($hoy);
        $esperadoActual = $this->esperadoPeriodo($nActual);
        $cobradoActual  = (float) $rends->filter(fn($m) => $this->periodoDeFecha($m->fecha) === $nActual)->sum('monto');
        $parcial        = $atrasadasN->isEmpty()
            && $cubiertas->contains($nActual)
            && ($esperadoActual - $cobradoActual) > 0.01;

        if ($atrasadasN->isNotEmpty()) {
            $nPend = $atrasadasN->min();               // la ventana vencida más antigua
        } elseif ($parcial) {
            $nPend = $nActual;                          // completar la ventana en curso
        } else {
            $nPend = $vencidos + 1;                     // la siguiente ventana sin cubrir
            while ($cubiertas->contains($nPend)) $nPend++;
        }

        $esperado = $this->esperadoPeriodo($nPend);
        $cobrado  = (float) $rends->filter(fn($m) => $this->periodoDeFecha($m->fecha) === $nPend)->sum('monto');
        $proximo  = $parcial ? $hoy->copy() : $this->fechaCobro($nPend);

        // La fecha con la que se registra un cobro NUNCA debe ser futura: un
        // cobro es un hecho real, ocurre hoy o antes. Cuando la ventana pendiente
        // es la que corre HOY pero su corte programado cae a futuro (se cobra
        // adelantado dentro del periodo), la fecha real es hoy —que sigue cayendo
        // en esa misma ventana—. En atrasos $proximo ya es pasado y se conserva
        // para abonar a la ventana vencida. Si la ventana pendiente aún no
        // empieza (todo lo vencido está cubierto), se deja el corte programado:
        // no hay fecha ≤ hoy que pertenezca a esa ventana futura.
        $sugerida = ($proximo->gt($hoy) && $this->periodoDeFecha($hoy) === $nPend)
            ? $hoy->copy()
            : $proximo->copy();

        return (object) [
            'atrasados'      => $atrasadasN->count(),
            'monto_atrasado' => round($atrasadasN->sum(fn($n) => $this->esperadoPeriodo($n)), 2),
            'parcial'        => $parcial,
            'periodo'        => $nPend,
            'esperado'       => $esperado,
            'cobrado'        => $cobrado,
            'restante'       => round(max(0, $esperado - $cobrado), 2),
            'proximo'        => $proximo,
            // La fecha del movimiento determina la ventana a la que se abona
            'fecha_sugerida' => $sugerida,
        ];
    }

    /** Resumen histórico de la cuenta (KPIs de la tarjeta y del detalle). */
    public function statsResumen(): array
    {
        $rendimientos = $this->movimientos->where('tipo', 'rendimiento');
        $activos      = $this->inversoresActivos();

        return [
            'aportado_activo'       => (float) $activos->sum('aporte'),
            'rendimientos_cobrados' => (float) $rendimientos->sum('monto'),
            'total_reinvertido'     => (float) $rendimientos->sum('monto_reinversion'),
            'total_retornado'       => (float) $rendimientos->sum('monto_retornado'),
            'total_retiros_owner'   => (float) $this->movimientos->where('tipo', 'retiro')->sum('monto'),
            'n_rendimientos'        => $rendimientos->count(),
            'n_inversores_activos'  => $activos->count(),
        ];
    }

    /**
     * Serie para la gráfica "teórico vs real": el eje mezcla los cortes del
     * calendario con las fechas en que DE VERDAD se cobró (y el día de hoy),
     * de modo que un cobro adelantado o atrasado se ve en su fecha real.
     * El teórico se prorratea por día dentro de cada ventana usando el capital
     * vigente al inicio de esa ventana (aportes/retiros mueven la pendiente).
     */
    public function serieComparativa(?\Carbon\Carbon $hoy = null): array
    {
        $hoy    = ($hoy ?? now())->copy()->startOfDay();
        $inicio = $this->fecha_inicio->copy()->startOfDay();
        $rends  = $this->movimientos->where('tipo', 'rendimiento')
            ->sortBy(fn($m) => $m->fecha->timestamp)->values();

        $finReal = $rends->isNotEmpty() ? $rends->last()->fecha->copy()->startOfDay() : null;
        $limite  = ($finReal && $finReal->gt($hoy)) ? $finReal->copy() : $hoy->copy();
        if ($limite->lt($inicio)) $limite = $inicio->copy();

        // Horizonte: hasta el primer corte que cubre el límite (el próximo cobro)
        $nMax = 1;
        while ($nMax < 520 && $this->fechaCobro($nMax)->lt($limite)) $nMax++;

        // Esperado acumulado al cierre de cada periodo, para prorratear entre cortes
        $cortes  = [0 => $inicio];
        $acumEsp = [0 => 0.0];
        for ($k = 1; $k <= $nMax; $k++) {
            $cortes[$k]  = $this->fechaCobro($k);
            $acumEsp[$k] = round($acumEsp[$k - 1] + $this->esperadoPeriodo($k), 2);
        }

        $teoricoEn = function (\Carbon\Carbon $d) use ($cortes, $acumEsp, $nMax): float {
            if ($d->lte($cortes[0])) return 0.0;
            for ($k = 1; $k <= $nMax; $k++) {
                if ($d->lte($cortes[$k])) {
                    $dias = max(1, $cortes[$k - 1]->diffInDays($cortes[$k]));
                    $frac = min(1, $cortes[$k - 1]->diffInDays($d) / $dias);
                    return round($acumEsp[$k - 1] + ($acumEsp[$k] - $acumEsp[$k - 1]) * $frac, 2);
                }
            }
            return $acumEsp[$nMax];
        };

        $fechas = collect(array_values($cortes))
            ->concat($rends->map(fn($m) => $m->fecha->copy()->startOfDay()))
            ->when($hoy->gte($inicio), fn($c) => $c->push($hoy->copy()))
            ->unique(fn($d) => $d->toDateString())
            ->sortBy(fn($d) => $d->timestamp)
            ->values();

        $labels = $teo = $real = $cobros = [];
        foreach ($fechas as $d) {
            $labels[] = $d->equalTo($inicio) ? 'Inicio' : ($d->equalTo($hoy) ? 'Hoy' : $d->format('d/m/y'));
            $teo[]    = $teoricoEn($d);
            $cobros[] = $rends->contains(fn($m) => $m->fecha->isSameDay($d));
            // La línea real se corta en hoy/último cobro (null en cortes futuros)
            $real[]   = $d->gt($limite) ? null
                : round((float) $rends->filter(fn($m) => $m->fecha->lte($d))->sum('monto'), 2);
        }

        return [
            'labels'      => $labels,
            'teorico'     => $teo,
            'real'        => $real,
            'cobros'      => $cobros,
            'periodo'     => $this->periodo_label,
            'teorico_hoy' => $teoricoEn($hoy),
            'real_hoy'    => round((float) $rends->filter(fn($m) => $m->fecha->lte($hoy))->sum('monto'), 2),
        ];
    }

    /**
     * Evolución del capital que trabaja: cada aporte, retiro, salida de
     * inversor o reinversión capitalizada mueve la línea en su fecha real.
     */
    public function serieCapital(?\Carbon\Carbon $hoy = null): array
    {
        $hoy    = ($hoy ?? now())->copy()->startOfDay();
        $inicio = $this->fecha_inicio->copy()->startOfDay();
        $money  = fn($v) => '$' . number_format($v, 2);

        $eventos = $this->movimientos
            ->filter(fn($m) => in_array($m->tipo, ['aporte', 'retiro', 'salida_inversor'])
                || ($m->tipo === 'rendimiento' && $m->capitalizado && $m->monto_reinversion > 0))
            ->sortBy(fn($m) => $m->fecha->format('Y-m-d') . str_pad((string) $m->id, 12, '0', STR_PAD_LEFT))
            ->groupBy(fn($m) => $m->fecha->toDateString());

        $labels = $data = $detalles = [];
        $capital = 0.0;

        foreach ($eventos as $dia => $movs) {
            $lineas = [];
            foreach ($movs as $m) {
                if ($m->tipo === 'aporte') {
                    $capital += $m->monto;
                    $lineas[] = '+' . $money($m->monto) . ' aporte' . ($m->inversor ? ' de ' . $m->inversor->nombre : '');
                } elseif ($m->tipo === 'retiro') {
                    $capital -= $m->monto;
                    $lineas[] = '−' . $money($m->monto) . ' retiro del owner';
                } elseif ($m->tipo === 'salida_inversor') {
                    $capital -= $m->monto;
                    $lineas[] = '−' . $money($m->monto) . ' salida' . ($m->inversor ? ' de ' . $m->inversor->nombre : ' de inversor');
                } else {
                    $capital += $m->monto_reinversion;
                    $lineas[] = '+' . $money($m->monto_reinversion) . ' reinversión capitalizada';
                }
            }
            $capital    = round(max(0, $capital), 2);
            $d          = \Carbon\Carbon::parse($dia)->startOfDay();
            $labels[]   = $d->equalTo($inicio) ? 'Inicio' : $d->format('d/m/y');
            $data[]     = $capital;
            $detalles[] = $lineas;
        }

        // Punto final: el capital vigente al día de hoy
        $ultimoDia = $eventos->keys()->last();
        if ($ultimoDia === null || $hoy->toDateString() > $ultimoDia) {
            $labels[]   = 'Hoy';
            $data[]     = (float) $this->capital_actual;
            $detalles[] = [];
        }

        return ['labels' => $labels, 'data' => $data, 'detalles' => $detalles];
    }

    /**
     * Comparativa periodo a periodo: fecha programada vs fechas reales de
     * cobro, esperado vs cobrado y el estado de cada ventana.
     */
    public function resumenPeriodos(?\Carbon\Carbon $hoy = null): array
    {
        $hoy   = ($hoy ?? now())->copy()->startOfDay();
        $rends = $this->movimientos->where('tipo', 'rendimiento');

        $nHoy = $this->periodoDeFecha($hoy);
        $nMax = min(520, max($nHoy, (int) $rends->map(fn($m) => $this->periodoDeFecha($m->fecha))->max()));

        $filas = [];
        for ($n = 1; $n <= $nMax; $n++) {
            $enVentana = $rends->filter(fn($m) => $this->periodoDeFecha($m->fecha) === $n);
            $esperado  = $this->esperadoPeriodo($n);
            $cobrado   = round((float) $enVentana->sum('monto'), 2);

            if ($cobrado > 0 && $cobrado >= $esperado - 0.01) $estado = 'cubierto';
            elseif ($cobrado > 0)                             $estado = 'parcial';
            elseif ($n < $nHoy)                               $estado = 'atrasado';
            elseif ($n === $nHoy)                             $estado = 'en_curso';
            else                                              $estado = 'proximo';

            $filas[] = [
                'n'          => $n,
                'programado' => $this->fechaCobro($n),
                'capital'    => $this->capitalInicioPeriodo($n),
                'esperado'   => $esperado,
                'cobrado'    => $cobrado,
                'fechas'     => $enVentana->sortBy(fn($m) => $m->fecha->timestamp)
                    ->map(fn($m) => $m->fecha->format('d/m/y'))->values()->all(),
                'estado'     => $estado,
            ];
        }

        return $filas;
    }

    /**
     * Registra el rendimiento del periodo (el cobro al admin).
     *
     * Retorno FIJO por inversor: su retorno_mensual pactado, prorrateado al
     * periodo de la cuenta. El fijo se paga UNA sola vez por ventana de cobro:
     * en cobros parciales (varios movimientos en la misma ventana) cada cobro
     * paga solo lo que falta del fijo y el resto se reinvierte; si el cobro no
     * alcanza para los fijos pendientes, se escalan proporcionalmente.
     * Lo usan el owner y el supervisor para que el reparto sea idéntico.
     */
    public function registrarRendimiento(float $monto, string $fecha, ?string $nota, bool $capitalizar, ?int $registradoPor = null): FinanciamientoMovimiento
    {
        $ventana = $this->periodoDeFecha(\Carbon\Carbon::parse($fecha));
        $pagados = $this->retornosPagadosPeriodo($ventana);

        $detalle    = [];
        $pendientes = [];
        $totalPend  = 0.0;

        foreach ($this->inversoresActivos() as $inv) {
            $fijo = round($inv->retorno_mensual / $this->periodos_por_mes, 2);
            $pend = round(max(0, $fijo - ($pagados[$inv->id] ?? 0)), 2);
            if ($pend <= 0) continue;
            $pendientes[] = ['inv' => $inv, 'pend' => $pend];
            $totalPend += $pend;
        }

        $escala         = ($totalPend > 0 && $totalPend > $monto) ? $monto / $totalPend : 1.0;
        $montoRetornado = 0.0;

        foreach ($pendientes as $fila) {
            $retorno = round($fila['pend'] * $escala, 2);
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

        if ($capitalizar && $montoReinversion > 0) {
            $this->capital_actual = round($this->capital_actual + $montoReinversion, 2);
            $this->save();
        }

        return FinanciamientoMovimiento::create([
            'financiamiento_id' => $this->id,
            'tipo'              => 'rendimiento',
            'monto'             => $monto,
            'monto_reinversion' => $montoReinversion,
            'monto_retornado'   => $montoRetornado,
            'capitalizado'      => $capitalizar && $montoReinversion > 0,
            'detalle'           => $detalle,
            'fecha'             => $fecha,
            'nota'              => $nota,
            'registrado_por'    => $registradoPor,
        ]);
    }
}
