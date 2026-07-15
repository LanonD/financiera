<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\Empleado;
use App\Models\PrestamoActividad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    /**
     * Collector: view their assigned loans and register payments
     */
    public function index()
    {
        $user     = Auth::user();
        $empleado = $user->empleado;
        $roles    = $user->getAllRoles();
        $isAdmin  = in_array('admin', $roles);
        // Para la vista seguimos pasando el puesto primario (la vista lo usa para mostrar capacidad)
        $puesto   = $user->puesto;

        // Helper: auto-activate mora and accumulate daily interest
        $acumularMora = function (\App\Models\Prestamo $p): void {
            if (!in_array($p->estatus, ['Activo', 'Atrasado'])) return;

            // Auto-activate $10/day when a payment is overdue
            $primerVencido = $p->pagos()
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->where('fecha_programada', '<', now()->toDateString())
                ->orderBy('fecha_programada')
                ->first();

            if ($primerVencido) {
                if ($p->estatus === 'Activo') {
                    $p->estatus = 'Atrasado';
                    $p->save();
                }
            }

            if (!((float)$p->interes_diario > 0 && ($p->interes_mora_activo || $p->estatus === 'Atrasado'))) return;

            $hoy   = now()->toDateString();
            $desde = $p->fecha_ultimo_interes ? $p->fecha_ultimo_interes->toDateString() : $hoy;
            $dias  = (int) \Carbon\Carbon::parse($desde)->diffInDays($hoy);
            if ($dias > 0) {
                $p->interes_acumulado    = round((float)$p->interes_acumulado + ($dias * (float)$p->interes_diario), 2);
                $p->fecha_ultimo_interes = $hoy;
                $p->save();
            }
        };

        $adminId = $user->adminId();

        $isCollectorOrPromo = !$isAdmin
            && ($empleado !== null)
            && (in_array('collector', $roles) || in_array('promo', $roles));

        if ($isCollectorOrPromo) {
            // Collector/promo ven solo los préstamos donde están asignados como cobradores
            $prestamos = Prestamo::with(['cliente'])
                ->where('admin_id', $adminId)
                ->where('cobrador_id', $empleado->id)
                ->whereIn('estatus', ['Activo', 'Atrasado'])
                ->get()
                ->map(function ($p) use ($acumularMora) {
                    $acumularMora($p);
                    $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])->orderBy('fecha_programada')->first();
                    // toDateString() evita que Carbon genere '2026-04-15 00:00:00' que rompe comparaciones de string
                    $p->proximo_pago = $next?->fecha_programada?->toDateString();
                    if ($next && $p->proximo_pago < now()->toDateString()) {
                        // Carbon 3: diffInDays es con signo — partir de la fecha vencida para obtener días positivos
                        $p->dias_atraso = (int) $next->fecha_programada->startOfDay()->diffInDays(now()->startOfDay());
                    } else {
                        $p->dias_atraso = 0;
                    }
                    return $p;
                });

            $cobrador = $empleado;
        } else {
            // Admin sees all loans within their tenant scope
            $prestamos = Prestamo::with(['cliente', 'cobrador'])
                ->where('admin_id', $adminId)
                ->whereIn('estatus', ['Activo', 'Atrasado'])
                ->get()
                ->map(function ($p) use ($acumularMora) {
                    $acumularMora($p);
                    $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])->orderBy('fecha_programada')->first();
                    $p->proximo_pago = $next?->fecha_programada?->toDateString();
                    $p->dias_atraso  = 0;
                    return $p;
                });
            $cobrador = $empleado;
        }

        return view('collector.cobros', compact('prestamos', 'cobrador', 'puesto'));
    }

    /**
     * Admin + Promo: monitor cobros grouped by cobrador for today.
     * Promo only sees cobradores from their own team (promotor_id = their empleado id).
     */
    public function monitor(Request $request)
    {
        $user        = Auth::user();
        $adminId     = $user->adminId();
        $roles       = $user->getAllRoles();
        $isAdmin     = in_array('admin', $roles);
        $empleado    = $user->empleado;
        $hoy         = now()->toDateString();
        $cobradorSel = (int) $request->query('cobrador', 0);

        // Cobradores visibles: admin → todos; promo → solo su equipo
        $cobradoresQuery = Empleado::where('admin_id', $adminId)->where('activo', true);
        if (!$isAdmin && $empleado) {
            $cobradoresQuery->where('promotor_id', $empleado->id);
        }

        $cobradores = $cobradoresQuery->get()
            ->filter(fn($e) => $e->hasRole('collector'))
            ->values();

        // Loans with a payment due today OR overdue, per cobrador
        $prestamosPorCobrador = collect();
        foreach ($cobradores as $cob) {
            $loansQuery = Prestamo::with(['cliente', 'pagos'])
                ->where('admin_id', $adminId)
                ->where('cobrador_id', $cob->id)
                ->whereIn('estatus', ['Activo', 'Atrasado']);

            // Promo: solo sus préstamos
            if (!$isAdmin && $empleado) {
                $loansQuery->where('promotor_id', $empleado->id);
            }

            $loans = $loansQuery->get()
                ->map(function ($p) use ($hoy) {
                    $next = $p->pagos
                        ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                        ->sortBy('fecha_programada')
                        ->first();
                    $p->proximo_pago  = $next?->fecha_programada?->toDateString();
                    $p->cuota_hoy     = $next?->monto_cuota ?? 0;
                    $p->pago_id_hoy   = $next?->id;
                    $p->mora          = (float)($p->interes_acumulado ?? 0);
                    $p->es_hoy        = $p->proximo_pago !== null && $p->proximo_pago <= $hoy;
                    $p->pagado_hoy    = $p->pagos
                        ->whereIn('estatus', ['Pagado', 'Parcial'])
                        ->where(fn($pg) => $pg->fecha_pago?->toDateString() === $hoy)
                        ->isNotEmpty();
                    return $p;
                });

            $cob->loans_hoy   = $loans->filter(fn($l) => $l->es_hoy)->sortByDesc('mora')->values();
            $cob->loans_otros = $loans->filter(fn($l) => !$l->es_hoy)->values();
            $cob->total_hoy   = $cob->loans_hoy->count();
            $cob->cobrados    = $cob->loans_hoy->filter(fn($l) => $l->pagado_hoy)->count();
            $cob->pendientes  = $cob->total_hoy - $cob->cobrados;
            $prestamosPorCobrador[$cob->id] = $cob;
        }

        $cobradorSelObj = $cobradorSel ? $prestamosPorCobrador->get($cobradorSel) : $prestamosPorCobrador->first();

        return view('admin.cobros_monitor', compact(
            'cobradores', 'prestamosPorCobrador', 'cobradorSel', 'cobradorSelObj', 'hoy'
        ));
    }

    /**
     * Admin: assign collectors to loans
     */
    public function asignar(Request $request)
    {
        $user             = Auth::user();
        $adminId          = $user->adminId();
        $roles            = $user->getAllRoles();
        $isAdmin          = in_array('admin', $roles);
        $isPromo          = !$isAdmin && in_array('promo', $roles);
        $empleado         = $user->empleado;

        $filtroDesde      = $request->query('desde', '');
        $filtroHasta      = $request->query('hasta', '');
        $filtroSinCobrador= (bool)$request->query('sin_cobrador', false);
        $filtroBusqueda   = $request->query('busqueda', '');

        $query = Prestamo::with(['cliente', 'cobrador'])
            ->where('admin_id', $adminId)
            ->whereIn('estatus', ['Activo', 'Atrasado']);

        // Promo: only sees loans where they are the assigned promotor
        if ($isPromo && $empleado) {
            $query->where('promotor_id', $empleado->id);
        }

        if ($filtroSinCobrador) {
            $query->whereNull('cobrador_id');
        }
        if ($filtroBusqueda) {
            $query->whereHas('cliente', fn($q) => $q->where('nombre', 'like', "%{$filtroBusqueda}%"));
        }

        $prestamos = $query->get()->map(function ($p) {
            $next    = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])->orderBy('fecha_programada')->first();
            $vencido = $next && $next->fecha_programada->toDateString() < now()->toDateString();

            // Self-heal: préstamo ya liquidado que quedó con estatus activo →
            // finalizarlo y sacarlo de la lista de asignación
            if ((!$next || (float)$p->saldo_actual <= 0) && $p->finalizarSiLiquidado()) {
                return null;
            }

            // Préstamo vencido que sigue marcado Activo (refinanciados, sincronización
            // offline, etc.) → reflejarlo como Atrasado para que aparezca en su sección.
            // Guardar ANTES de asignar los atributos sintéticos (no son columnas).
            if ($vencido && $p->estatus === 'Activo') {
                $p->estatus = 'Atrasado';
                $p->save();
            }

            $p->proximo_pago = $next?->fecha_programada?->toDateString();
            // Carbon 3: diffInDays es con signo — partir de la fecha vencida para obtener días positivos
            $p->dias_atraso  = $vencido
                ? (int) $next->fecha_programada->startOfDay()->diffInDays(now()->startOfDay())
                : 0;
            // Check if paid today
            $pagadoHoy = $p->pagos()
                ->whereIn('estatus', ['Pagado', 'Parcial'])
                ->whereDate('fecha_pago', now()->toDateString())
                ->first();
            $p->pagado_hoy    = $pagadoHoy ? 1 : 0;
            $p->tipo_pago_hoy = $pagadoHoy?->estatus;
            return $p;
        })->filter()->values();

        if ($filtroDesde || $filtroHasta) {
            $prestamos = $prestamos->filter(function ($p) use ($filtroDesde, $filtroHasta) {
                $pp = $p->proximo_pago;
                if (!$pp && $p->dias_atraso <= 0) return false;
                if ($filtroDesde && $pp && $pp < $filtroDesde && $p->dias_atraso <= 0) return false;
                if ($filtroHasta && $pp && $pp > $filtroHasta) return false;
                return true;
            });
        }

        // Cobradores disponibles:
        // Admin → todos los cobradores del tenant
        // Promo → solo cobradores asignados a su equipo (promotor_id = su empleado.id)
        $cobradoresQuery = Empleado::where('activo', true)->where('admin_id', $adminId);

        if ($isPromo && $empleado) {
            $cobradoresQuery->where('promotor_id', $empleado->id);
        }

        $cobradores = $cobradoresQuery->get()
            ->filter(fn($e) => $e->hasRole('collector'))
            ->values();

        return view('admin.cobros_asignar', compact('prestamos', 'cobradores', 'filtroDesde', 'filtroHasta', 'filtroSinCobrador', 'filtroBusqueda'));
    }

    /**
     * Admin: save collector assignments
     */
    public function guardarAsignacion(Request $request)
    {
        $user         = Auth::user();
        $adminId      = $user->adminId();
        $roles        = $user->getAllRoles();
        $isAdmin      = in_array('admin', $roles);
        $isPromo      = !$isAdmin && in_array('promo', $roles);
        $empleado     = $user->empleado;
        $asignaciones = $request->input('asignacion', []);
        $guardados    = 0;

        // Build allowed cobrador IDs for promo (their team only)
        $cobradoresPermitidos = null;
        if ($isPromo && $empleado) {
            $cobradoresPermitidos = Empleado::where('promotor_id', $empleado->id)
                ->where('activo', true)
                ->pluck('id')
                ->toArray();
        }

        foreach ($asignaciones as $prestamoId => $cobradorId) {
            $query = Prestamo::where('id', $prestamoId)->where('admin_id', $adminId);

            // Promo: can only modify loans assigned to them
            if ($isPromo && $empleado) {
                $query->where('promotor_id', $empleado->id);
            }

            $prestamo = $query->first();
            if (!$prestamo) continue;

            // Promo: can only assign cobradores from their team
            if ($isPromo && $cobradoresPermitidos !== null && $cobradorId > 0) {
                if (!in_array((int)$cobradorId, $cobradoresPermitidos)) continue;
            }
            $anteriorCobradorId = $prestamo->cobrador_id;
            $prestamo->cobrador_id = $cobradorId > 0 ? $cobradorId : null;
            $prestamo->save();
            // Also assign to pending pagos
            if ($cobradorId > 0) {
                Pago::where('prestamo_id', $prestamoId)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                    ->update(['cobrador_id' => $cobradorId]);
            }
            // Log if changed
            if ($anteriorCobradorId !== $prestamo->cobrador_id) {
                $cobrador = $cobradorId > 0 ? Empleado::find($cobradorId) : null;
                PrestamoActividad::log($prestamoId, 'cobrador',
                    $cobrador
                        ? 'Cobrador asignado: ' . $cobrador->nombre . '.'
                        : 'Cobrador removido.',
                    ['cobrador_id' => $cobradorId > 0 ? $cobradorId : null, 'nombre' => $cobrador?->nombre]
                );
            }
            $guardados++;
        }

        return redirect()->route('cobros.asignar')->with('success', $guardados . ' asignación(es) guardada(s).');
    }

    /**
     * Collector: register a payment (JSON endpoint)
     * Mora interest is charged FIRST, remainder applied to the scheduled cuota.
     */
    public function registrar(Request $request)
    {
        $user     = Auth::user();
        $empleado = $user->empleado;

        $adminId = $user->adminId();
        $cobros  = $request->json()->all(); // { prestamoId: {tipo, monto, nota} }

        $registrados = 0;
        $errors      = [];

        foreach ($cobros as $prestamoId => $cobro) {
            // Evitar que valores de la iteración anterior contaminen el log de esta
            unset($tipo, $diferencia, $nextPago);

            $prestamo = Prestamo::where('id', $prestamoId)
                ->where('admin_id', $adminId)
                ->first();
            if (!$prestamo) { $errors[] = "Préstamo #{$prestamoId} no encontrado"; continue; }

            // ── 1. Auto-activate mora and bring interest up to date ─────────────
            if (in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
                $primerVencido = Pago::where('prestamo_id', $prestamoId)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                    ->where('fecha_programada', '<', now()->toDateString())
                    ->orderBy('fecha_programada')
                    ->first();
                if ($primerVencido) {
                    if ($prestamo->estatus === 'Activo') $prestamo->estatus = 'Atrasado';
                }
            }

            if ((float)$prestamo->interes_diario > 0
                && ($prestamo->interes_mora_activo || $prestamo->estatus === 'Atrasado')) {
                $hoy      = now()->toDateString();
                $desde    = $prestamo->fecha_ultimo_interes
                    ? $prestamo->fecha_ultimo_interes->toDateString()
                    : $hoy;
                $diasMora = (int) \Carbon\Carbon::parse($desde)->diffInDays($hoy);
                if ($diasMora > 0) {
                    $prestamo->interes_acumulado    = round((float)$prestamo->interes_acumulado + ($diasMora * (float)$prestamo->interes_diario), 2);
                    $prestamo->fecha_ultimo_interes = $hoy;
                }
            }

            // ── 2. Get next pending cuota ────────────────────────────────────────
            $pago = Pago::where('prestamo_id', $prestamoId)
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->orderBy('numero_pago')
                ->first();

            if (!$pago) { $errors[] = "Sin pago pendiente en préstamo #{$prestamoId}"; continue; }

            $montoRecibido = (float)($cobro['monto'] ?? 0);
            if ($montoRecibido <= 0) continue;

            $nota = $cobro['nota'] ?? null;

            // ── 3. Apply to mora FIRST ───────────────────────────────────────────
            $moraPendiente = (float)$prestamo->interes_acumulado;
            $pagoMora      = 0;

            if ($moraPendiente > 0) {
                $pagoMora              = min($montoRecibido, $moraPendiente);
                $prestamo->interes_acumulado = round($moraPendiente - $pagoMora, 2);
                $montoRecibido        -= $pagoMora;

                $notaMora = 'Mora cobrada: $' . number_format($pagoMora, 2);
                $nota     = $nota ? $nota . ' | ' . $notaMora : $notaMora;
            }

            // ── 4. Apply remainder to cuota ─────────────────────────────────────
            $excedenteInfo = null;
            if ($montoRecibido > 0) {
                $yaCobradoCuota = (float)($pago->monto_cobrado ?? 0);
                $dueCuota = round((float)$pago->monto_cuota - $yaCobradoCuota, 2);
                $tipo     = $montoRecibido >= $dueCuota ? 'Pagado' : 'Parcial';

                // Topar el cobro de ESTA cuota a su monto; el excedente se reparte adelante
                $cobroCuota = $tipo === 'Pagado' ? $dueCuota : $montoRecibido;
                $excedente  = $tipo === 'Pagado' ? round($montoRecibido - $dueCuota, 2) : 0.0;

                $pago->monto_cobrado = $tipo === 'Pagado'
                    ? round((float)$pago->monto_cuota, 2)
                    : round($yaCobradoCuota + $cobroCuota, 2);
                $pago->tipo_cobro    = $tipo === 'Pagado' ? 'completo' : 'parcial';
                $pago->nota_cobro    = $nota;
                $pago->fecha_pago    = now()->toDateString();
                $pago->estatus       = $tipo;
                $pago->cobrador_id   = $empleado?->id;
                $pago->save();

                // Saldo = deuda total pendiente → baja por TODO lo cobrado (incluye excedente)
                $prestamo->saldo_actual = max(0, round((float)$prestamo->saldo_actual - $montoRecibido, 2));

                // ── Excedente (pagó de más) → repartir a las cuotas siguientes ──
                if ($excedente > 0.01) {
                    $excedenteInfo = $this->aplicarExcedenteACuotas($pago, $excedente, $empleado?->id, now()->toDateString());
                }

                if ($tipo === 'Pagado') {
                    $remaining = Pago::where('prestamo_id', $prestamoId)
                        ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                        ->count();
                    if ($remaining > 0) {
                        $prestamo->estatus = 'Activo';
                    }
                }

                // ── 5. Parcial: conserva su saldo pendiente en la misma cuota ──
                // Una cuota parcial conserva su saldo pendiente; no se mueve a otra cuota.
            } elseif ($pagoMora > 0) {
                // Payment covered only mora (nothing left for cuota)
                // Note it on the pago without changing its estatus
                $pago->nota_cobro = ($pago->nota_cobro ? $pago->nota_cobro . ' | ' : '') . $nota;
                $pago->save();
            }

            $prestamo->save();

            // Finalizar si quedó liquidado (última cuota pagada, o saldo y mora en $0)
            $prestamo->finalizarSiLiquidado();

            // Log actividad
            $quien = $user->empleado?->nombre ?? $user->usuario;
            $tipoLog = isset($tipo) ? $tipo : 'Solo mora';
            $desc  = "Pago #{$pago->numero_pago} registrado por {$quien} — $" . number_format($montoRecibido + $pagoMora, 2) . " ({$tipoLog}).";
            if ($pagoMora > 0) {
                $desc .= " Mora cobrada: $" . number_format($pagoMora, 2) . '.';
            }
            if (isset($tipo) && $tipo === 'Parcial' && isset($diferencia) && $diferencia > 0 && isset($nextPago) && $nextPago) {
                $desc .= " Diferencia de \$$diferencia pasó a cuota #{$nextPago->numero_pago}.";
            }
            $desc .= $this->describirExcedente($excedenteInfo);
            if ($prestamo->estatus === 'Finalizado') {
                $desc .= ' ✓ Préstamo finalizado.';
            }
            PrestamoActividad::log($prestamo->id, 'pago', $desc, [
                'pago_id'    => $pago->id,
                'monto'      => $montoRecibido + $pagoMora,
                'tipo'       => $tipoLog,
                'mora'       => $pagoMora,
                'cobrador_id'=> $empleado?->id,
            ]);

            $registrados++;
        }

        return response()->json([
            'ok'          => true,
            'registrados' => $registrados,
            'errors'      => $errors,
        ]);
    }

    /**
     * Admin / Promo: register an immediate extra payment outside the plan schedule.
     * Priority: 1) mora, 2) interest of next pending cuota, 3) capital (saldo_actual).
     */
    public function registrarExtra(Request $request, $id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'nota'  => 'nullable|string|max:255',
        ]);

        $user     = Auth::user();
        $empleado = $user->empleado;
        $adminId  = $user->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        if (!in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            return redirect()->back()->with('error', 'Solo se puede registrar un cobro extra en préstamos activos.');
        }

        // Bring mora up to date
        if ((float)$prestamo->interes_diario > 0
            && ($prestamo->interes_mora_activo || $prestamo->estatus === 'Atrasado')) {
            $hoy   = now()->toDateString();
            $desde = $prestamo->fecha_ultimo_interes ? $prestamo->fecha_ultimo_interes->toDateString() : $hoy;
            $dias  = (int) \Carbon\Carbon::parse($desde)->diffInDays($hoy);
            if ($dias > 0) {
                $prestamo->interes_acumulado    = round((float)$prestamo->interes_acumulado + ($dias * (float)$prestamo->interes_diario), 2);
                $prestamo->fecha_ultimo_interes = $hoy;
            }
        }

        $montoTotal = (float)$request->monto;
        $monto      = $montoTotal;
        $nota       = $request->nota ?? 'Cobro inmediato';

        // ── 1. Mora first ────────────────────────────────────────────────────
        $moraPendiente = (float)$prestamo->interes_acumulado;
        $pagoMora      = 0;
        if ($moraPendiente > 0 && $monto > 0) {
            $pagoMora                    = min($monto, $moraPendiente);
            $prestamo->interes_acumulado = round($moraPendiente - $pagoMora, 2);
            $monto                      -= $pagoMora;
            $nota .= ' | Mora: $' . number_format($pagoMora, 2);
        }

        // ── 2. El resto salda las cuotas del plan: primero lo VENCIDO, luego
        //       adelanta futuras. Así un cobro reduce el atraso peso por peso
        //       (antes solo bajaba el saldo sin tocar las cuotas, y el atraso
        //        mostrado no cambiaba). ─────────────────────────────────────
        $fecha    = now()->toDateString();
        $notaPlan = 'Cobro inmediato' . ($request->nota ? ' — ' . $request->nota : '');

        $plan = ['aplicado' => 0.0, 'pagadas' => [], 'parcial' => null, 'sobrante' => 0.0];
        if ($monto > 0) {
            $plan = $this->aplicarPagoAPlan($prestamo->id, $monto, $empleado?->id, $fecha, $notaPlan);
            if ($plan['aplicado'] > 0) {
                // Saldo = deuda total pendiente → baja por lo aplicado a cuotas (la mora va aparte)
                $prestamo->saldo_actual = max(0, round((float)$prestamo->saldo_actual - $plan['aplicado'], 2));
            }
        }

        $prestamo->save();

        // ── 3. Actualizar estado: al corriente / finalizado ─────────────────
        $prestamo->recalcularAtraso();
        $prestamo->finalizarSiLiquidado();
        $mensajeFin = $prestamo->estatus === 'Finalizado' ? ' ✓ Préstamo finalizado.' : '';

        // ── 4. Registrar actividad y responder ──────────────────────────────
        $quien   = Auth::user()->empleado?->nombre ?? Auth::user()->usuario;
        $resumen = '';
        if ($pagoMora > 0)            $resumen .= ' Mora: $' . number_format($pagoMora, 2) . '.';
        if (!empty($plan['pagadas'])) $resumen .= ' Cuotas saldadas: #' . implode(', #', $plan['pagadas']) . '.';
        if (!empty($plan['parcial'])) $resumen .= ' Abono $' . number_format($plan['parcial']['monto'], 2) . ' a cuota #' . $plan['parcial']['num'] . '.';
        if ($plan['sobrante'] > 0.01) $resumen .= ' Sobrante $' . number_format($plan['sobrante'], 2) . ' a favor del cliente.';

        $descExtra = 'Cobro inmediato de $' . number_format($montoTotal, 2) . ' registrado por ' . $quien . '.' . $resumen . $mensajeFin;
        PrestamoActividad::log($prestamo->id, 'cobro_extra', $descExtra, [
            'total'    => $montoTotal,
            'mora'     => $pagoMora,
            'aplicado' => $plan['aplicado'],
            'sobrante' => $plan['sobrante'],
            'saldo'    => $prestamo->saldo_actual,
        ]);

        return redirect()->back()->with('success',
            'Cobro inmediato de $' . number_format($montoTotal, 2) . ' registrado.' . $resumen . $mensajeFin);
    }

    /**
     * Admin / Promo: schedule a custom future payment outside the plan.
     */
    public function agendarCobro(Request $request, $id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date|after_or_equal:today',
            'nota'  => 'nullable|string|max:255',
        ]);

        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        if (!in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            return redirect()->back()->with('error', 'Solo se puede agendar un cobro en préstamos activos.');
        }

        $empleado  = Auth::user()->empleado;
        $maxNumero = Pago::where('prestamo_id', $prestamo->id)->max('numero_pago') ?? 0;

        Pago::create([
            'prestamo_id'      => $prestamo->id,
            'cobrador_id'      => $empleado?->id,
            'numero_pago'      => $maxNumero + 1,
            'monto_cuota'      => $request->monto,
            'interes'          => 0,
            'capital'          => $request->monto,
            'saldo_restante'   => $prestamo->saldo_actual,
            'monto_cobrado'    => null,
            'tipo_cobro'       => null,
            'tipo_pago'        => 'agendado',
            'nota_cobro'       => $request->nota ?? 'Cobro agendado',
            'fecha_programada' => $request->fecha,
            'fecha_pago'       => null,
            'estatus'          => 'Pendiente',
        ]);

        PrestamoActividad::log($prestamo->id, 'agendado',
            'Cobro de $' . number_format($request->monto, 2) . ' agendado para ' .
            \Carbon\Carbon::parse($request->fecha)->format('d/m/Y') .
            ($request->nota ? ' — ' . $request->nota : '') .
            ' (por ' . (Auth::user()->empleado?->nombre ?? Auth::user()->usuario) . ').',
            ['monto' => $request->monto, 'fecha' => $request->fecha]
        );

        return redirect()->back()->with('success', 'Cobro de $' . number_format($request->monto, 2) . ' agendado para ' . \Carbon\Carbon::parse($request->fecha)->format('d/m/Y') . '.');
    }

    /**
     * Admin / Promo: toggle payment hold (pago diferido).
     * ON:  marks next pending plan pago as "congelado" (paid $0, merged into the following cuota).
     *      The following plan pago gets its cuota doubled.
     * OFF: reverts the hold — restores both pagos to their original state.
     */
    public function togglePaymentHold(Request $request, $id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        if (!in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            return redirect()->back()->with('error', 'El préstamo no está activo.');
        }

        // ── Cancel hold ─────────────────────────────────────────────────────
        if ($prestamo->payment_hold) {
            $congelado = Pago::where('prestamo_id', $id)
                ->where('tipo_pago', 'congelado')
                ->where('estatus', 'Pagado')
                ->first();

            if ($congelado) {
                // Parse original fecha stored in nota: format "congelado:{YYYY-MM-DD}:{nota_original}"
                $partes          = explode('|ORIG:', $congelado->nota_cobro ?? '', 2);
                $originalFecha   = $partes[1] ?? null;
                $originalNota    = null;
                if (str_contains($partes[0] ?? '', 'Pago diferido')) {
                    $originalNota = null;
                }

                // Find the doubled pago (next by numero_pago)
                $doblePago = Pago::where('prestamo_id', $id)
                    ->where('numero_pago', $congelado->numero_pago + 1)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                    ->first();

                if ($doblePago) {
                    $doblePago->monto_cuota = $prestamo->cuota;
                    $doblePago->nota_cobro  = null;
                    $doblePago->save();
                }

                // Restore congelado pago
                $congelado->tipo_pago        = 'plan';
                $congelado->estatus          = 'Pendiente';
                $congelado->monto_cobrado    = null;
                $congelado->tipo_cobro       = null;
                $congelado->fecha_pago       = null;
                $congelado->nota_cobro       = $originalNota;
                if ($originalFecha) {
                    $congelado->fecha_programada = $originalFecha;
                }
                $congelado->save();
            }

            $prestamo->payment_hold = false;
            $prestamo->save();

            PrestamoActividad::log($prestamo->id, 'pago_diferido',
                'Pago diferido cancelado por ' . (Auth::user()->empleado?->nombre ?? Auth::user()->usuario) . '. Plan de pagos restaurado.',
                ['accion' => 'cancelado']
            );

            return redirect()->back()->with('success', 'Pago diferido cancelado. El plan de pagos fue restaurado.');
        }

        // ── Activate hold ────────────────────────────────────────────────────
        $planPagos = Pago::where('prestamo_id', $id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->where('tipo_pago', 'plan')
            ->orderBy('numero_pago')
            ->take(2)
            ->get();

        if ($planPagos->count() < 2) {
            return redirect()->back()->with('error', 'Se necesitan al menos 2 pagos del plan pendientes para establecer un pago diferido.');
        }

        $pago1 = $planPagos[0]; // to be skipped/frozen
        $pago2 = $planPagos[1]; // to be doubled

        // Mark pago1 as congelado — store original date in the nota for restoration
        $pago1->tipo_pago     = 'congelado';
        $pago1->estatus       = 'Pagado';
        $pago1->monto_cobrado = 0;
        $pago1->tipo_cobro    = 'completo';
        $pago1->fecha_pago    = now()->toDateString();
        $pago1->nota_cobro    = 'Pago diferido - incluido en siguiente cuota|ORIG:' . $pago1->fecha_programada->toDateString();
        $pago1->fecha_programada = $pago2->fecha_programada; // visual: move to same date as double pago
        $pago1->save();

        // Double pago2 cuota
        $pago2->monto_cuota = round((float)$pago2->monto_cuota + (float)$prestamo->cuota, 2);
        $pago2->nota_cobro  = 'Cuota doble (incluye pago diferido anterior)';
        $pago2->save();

        $prestamo->payment_hold = true;
        $prestamo->save();

        PrestamoActividad::log($prestamo->id, 'pago_diferido',
            'Pago diferido activado por ' . (Auth::user()->empleado?->nombre ?? Auth::user()->usuario) .
            '. Cuota #' . $pago1->numero_pago . ' diferida — siguiente cuota doble: $' . number_format($pago2->monto_cuota, 2) . '.',
            ['accion' => 'activado', 'cuota_diferida' => $pago1->numero_pago, 'cuota_doble' => $pago2->monto_cuota]
        );

        return redirect()->back()->with('success', 'Pago diferido establecido. La siguiente cuota será doble ($' . number_format($pago2->monto_cuota, 2) . ').');
    }

    /**
     * Promo / Admin: assign themselves as the collector of a loan
     */
    public function asignarme(Request $request, $id)
    {
        $user     = Auth::user();
        $empleado = $user->empleado;

        if (!$empleado) {
            return redirect()->back()->with('error', 'Tu cuenta no tiene un perfil de empleado asociado.');
        }

        $adminId  = $user->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        // Promo solo puede asignarse a sus propios préstamos
        if ($user->puesto === 'promo' && $prestamo->promotor_id !== $empleado->id) {
            abort(403, 'Solo puedes asignarte a tus propios préstamos.');
        }

        $prestamo->cobrador_id = $empleado->id;
        $prestamo->save();

        // Propagar la asignación a los pagos pendientes
        Pago::where('prestamo_id', $id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->update(['cobrador_id' => $empleado->id]);

        PrestamoActividad::log($prestamo->id, 'cobrador',
            $empleado->nombre . ' se asignó como cobrador del préstamo.',
            ['cobrador_id' => $empleado->id, 'nombre' => $empleado->nombre]
        );

        return redirect()->back()->with('success', 'Te has asignado como cobrador de este préstamo.');
    }

    /** Concatena una nota nueva a la existente con separador. */
    private function concatNota(?string $existente, string $nueva): string
    {
        return $existente ? $existente . ' | ' . $nueva : $nueva;
    }

    /**
     * Aplica un pago a las cuotas del plan pendientes, de la más antigua a la
     * más reciente (primero salda lo VENCIDO, luego adelanta cuotas futuras).
     *
     * Abono parcial sin diferir: la cuota que queda a medias conserva su
     * remanente adeudado (sigue contando como atraso hasta cubrirse), de modo
     * que un cobro reduce el atraso peso por peso.
     *
     * @return array{aplicado: float, pagadas: int[], parcial: ?array{num:int,monto:float}, sobrante: float}
     */
    private function aplicarPagoAPlan(int $prestamoId, float $monto, ?int $cobradorId, string $fecha, string $nota): array
    {
        $restante = round($monto, 2);
        $pagadas  = [];
        $parcial  = null;

        $cuotas = Pago::where('prestamo_id', $prestamoId)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->where('tipo_pago', 'plan')
            ->orderBy('fecha_programada')
            ->orderBy('numero_pago')
            ->get();

        foreach ($cuotas as $c) {
            if ($restante <= 0.005) break;
            $ya  = (float) ($c->monto_cobrado ?? 0);
            $due = round((float) $c->monto_cuota - $ya, 2);
            if ($due <= 0) continue;

            if ($restante >= $due - 0.005) {
                // Cuota saldada por completo
                $c->monto_cobrado = round((float) $c->monto_cuota, 2);
                $c->tipo_cobro    = 'completo';
                $c->estatus       = 'Pagado';
                $c->fecha_pago    = $fecha;
                $c->cobrador_id   = $cobradorId;
                $c->nota_cobro    = $this->concatNota($c->nota_cobro, $nota);
                $c->save();
                $pagadas[] = $c->numero_pago;
                $restante  = round($restante - $due, 2);
            } else {
                // Abono parcial sobre la cuota (conserva el remanente adeudado)
                $c->monto_cobrado = round($ya + $restante, 2);
                $c->tipo_cobro    = 'parcial';
                $c->estatus       = 'Parcial';
                $c->fecha_pago    = $fecha;
                $c->cobrador_id   = $cobradorId;
                $c->nota_cobro    = $this->concatNota($c->nota_cobro, $nota . ' (abono)');
                $c->save();
                $parcial  = ['num' => $c->numero_pago, 'monto' => round($restante, 2)];
                $restante = 0.0;
                break;
            }
        }

        return [
            'aplicado' => round($monto - $restante, 2),
            'pagadas'  => $pagadas,
            'parcial'  => $parcial,
            'sobrante' => round($restante, 2),
        ];
    }

    /** Arma un fragmento de mensaje legible describiendo cómo se repartió el excedente. */
    private function describirExcedente(?array $info): string
    {
        if (!$info) return '';
        $partes = [];
        if (!empty($info['cubiertas'])) {
            $nums = implode(', #', $info['cubiertas']);
            $partes[] = count($info['cubiertas']) === 1
                ? "adelantó la cuota #{$nums}"
                : "adelantó las cuotas #{$nums}";
        }
        if (!empty($info['parcial'])) {
            $partes[] = 'abonó $' . number_format($info['parcial']['monto'], 2) .
                ' a la cuota #' . $info['parcial']['num'] .
                ' (pendiente $' . number_format($info['parcial']['pendiente'], 2) . ')';
        }
        if (($info['sobrante'] ?? 0) > 0.01) {
            $partes[] = 'sobró $' . number_format($info['sobrante'], 2) . ' a favor del cliente';
        }
        return $partes ? ' Excedente: ' . implode(', ', $partes) . '.' : '';
    }

    /**
     * Reparte un EXCEDENTE (dinero sobrante tras cubrir la cuota actual) a las
     * cuotas pendientes POSTERIORES en orden cronológico — "pago adelantado".
     *
     * Cada cuota que el excedente cubre por completo se marca Pagada; si la
     * última cuota alcanzada queda a medias se marca Parcial y conserva su saldo
     * pendiente en esa misma cuota. Así el siguiente cobro sigue siendo esa cuota
     * hasta que quede cubierta completa.
     *
     * @return array{cubiertas: int[], parcial: ?array{num:int, monto:float, pendiente:float}, sobrante: float}
     *         sobrante = dinero que quedó tras cubrir TODAS las cuotas (préstamo pagado de más)
     */
    private function aplicarExcedenteACuotas(Pago $cuotaActual, float $excedente, ?int $cobradorId, string $fechaPago): array
    {
        $restante  = round($excedente, 2);
        $cubiertas = [];
        $parcial   = null;

        // Cuotas pendientes POSTERIORES a la cuota actual, en orden cronológico
        $siguientes = Pago::where('prestamo_id', $cuotaActual->prestamo_id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->where('id', '!=', $cuotaActual->id)
            ->orderBy('fecha_programada')
            ->orderBy('numero_pago')
            ->get()
            ->filter(function ($c) use ($cuotaActual) {
                $fa = (string) $cuotaActual->fecha_programada;
                $fc = (string) $c->fecha_programada;
                return $fc > $fa || ($fc === $fa && $c->numero_pago > $cuotaActual->numero_pago);
            })
            ->values();

        foreach ($siguientes as $c) {
            if ($restante < 0.01) break;
            $ya  = (float) ($c->monto_cobrado ?? 0);
            $due = round((float) $c->monto_cuota - $ya, 2);
            if ($due <= 0) continue;

            if ($restante >= $due - 0.001) {
                // El excedente cubre la cuota completa → Pagada
                $c->monto_cobrado = round((float) $c->monto_cuota, 2);
                $c->tipo_cobro    = 'completo';
                $c->estatus       = 'Pagado';
                $c->fecha_pago    = $fechaPago;
                $c->cobrador_id   = $cobradorId;
                $c->nota_cobro    = $this->concatNota($c->nota_cobro,
                    'Cubierta con excedente de cuota #' . $cuotaActual->numero_pago);
                $c->save();
                $cubiertas[] = $c->numero_pago;
                $restante = round($restante - $due, 2);
            } else {
                // El excedente cubre solo una parte: la cuota sigue pendiente por su saldo restante.
                $c->monto_cobrado = round($ya + $restante, 2);
                $c->tipo_cobro    = 'parcial';
                $c->estatus       = 'Parcial';
                $c->fecha_pago    = $fechaPago;
                $c->cobrador_id   = $cobradorId;
                $c->nota_cobro    = $this->concatNota($c->nota_cobro,
                    'Abono $' . number_format($restante, 2) . ' con excedente de cuota #' . $cuotaActual->numero_pago);

                $c->save();
                $parcial  = [
                    'num'       => $c->numero_pago,
                    'monto'     => round($restante, 2),
                    'pendiente' => round($due - $restante, 2),
                ];
                $restante = 0.0;
                break;
            }
        }

        return ['cubiertas' => $cubiertas, 'parcial' => $parcial, 'sobrante' => round($restante, 2)];
    }

    /**
     * Admin / Promo: register a payment for a specific cuota (pago) selected by the user.
     * Applies mora first, then the remainder to the chosen cuota.
     */
    public function pagarCuota(Request $request, $prestamoId)
    {
        $request->validate([
            'pago_id'       => 'required|integer|exists:pagos,id',
            'monto'         => 'required|numeric|min:0.01',
            'nota'          => 'nullable|string|max:255',
            'carry_forward' => 'nullable|boolean',
        ]);

        $user     = Auth::user();
        $empleado = $user->empleado;
        $adminId  = $user->adminId();
        $prestamo = Prestamo::where('id', $prestamoId)->where('admin_id', $adminId)->firstOrFail();

        // Verify the pago belongs to this prestamo and is payable
        $pago = Pago::where('id', $request->pago_id)
            ->where('prestamo_id', $prestamoId)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->firstOrFail();

        if (!in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            return redirect()->back()->with('error', 'El préstamo no está activo.');
        }

        // ── Bring mora up to date ───────────────────────────────────────────
        if ((float)$prestamo->interes_diario > 0
            && in_array($prestamo->estatus, ['Activo', 'Atrasado'])
            && ($prestamo->interes_mora_activo || $prestamo->estatus === 'Atrasado')) {
            $hoy   = now()->toDateString();
            $desde = $prestamo->fecha_ultimo_interes
                ? $prestamo->fecha_ultimo_interes->toDateString()
                : $hoy;
            $dias  = (int) \Carbon\Carbon::parse($desde)->diffInDays($hoy);
            if ($dias > 0) {
                $prestamo->interes_acumulado    = round((float)$prestamo->interes_acumulado + ($dias * (float)$prestamo->interes_diario), 2);
                $prestamo->fecha_ultimo_interes = $hoy;
            }
        }

        $montoRecibido = (float)$request->monto;
        $nota          = $request->nota ?? '';

        // ── Apply to mora first ─────────────────────────────────────────────
        $moraPendiente = (float)$prestamo->interes_acumulado;
        $pagoMora      = 0;
        if ($moraPendiente > 0 && $montoRecibido > 0) {
            $pagoMora                    = min($montoRecibido, $moraPendiente);
            $prestamo->interes_acumulado = round($moraPendiente - $pagoMora, 2);
            $montoRecibido              -= $pagoMora;
            $nota .= ($nota ? ' | ' : '') . 'Mora: $' . number_format($pagoMora, 2);
        }

        // ── Apply remainder to the chosen cuota ─────────────────────────────
        $excedenteInfo = null;
        if ($montoRecibido > 0) {
            $yaCobradoCuota = (float)($pago->monto_cobrado ?? 0);
            $dueCuota = round((float)$pago->monto_cuota - $yaCobradoCuota, 2);
            $tipo     = $montoRecibido >= $dueCuota ? 'Pagado' : 'Parcial';

            // Topar el cobro de ESTA cuota a su monto; el excedente se reparte adelante
            $cobroCuota = $tipo === 'Pagado' ? $dueCuota : $montoRecibido;
            $excedente  = $tipo === 'Pagado' ? round($montoRecibido - $dueCuota, 2) : 0.0;

            $pago->monto_cobrado = $tipo === 'Pagado'
                ? round((float)$pago->monto_cuota, 2)
                : round($yaCobradoCuota + $cobroCuota, 2);
            $pago->tipo_cobro    = $tipo === 'Pagado' ? 'completo' : 'parcial';
            $pago->nota_cobro    = $nota ?: null;
            $pago->fecha_pago    = now()->toDateString();
            $pago->estatus       = $tipo;
            $pago->cobrador_id   = $empleado?->id;
            $pago->save();

            // Saldo = deuda total pendiente → baja por TODO lo cobrado (incluye excedente)
            $prestamo->saldo_actual = max(0, round((float)$prestamo->saldo_actual - $montoRecibido, 2));

            // ── Excedente (pagó de más) → repartir a las cuotas siguientes ──
            if ($excedente > 0.01) {
                $excedenteInfo = $this->aplicarExcedenteACuotas($pago, $excedente, $empleado?->id, now()->toDateString());
            }

            // Check if all payments are now done
            if ($tipo === 'Pagado') {
                $remaining = Pago::where('prestamo_id', $prestamoId)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                    ->count();
                if ($remaining > 0) {
                    $prestamo->estatus = 'Activo';
                }
            }
        } elseif ($pagoMora > 0) {
            // Payment covered only mora, annotate pago but keep it pending
            $pago->nota_cobro = ($pago->nota_cobro ? $pago->nota_cobro . ' | ' : '') . $nota;
            $pago->save();
        }

        $prestamo->save();

        // Finalizar si quedó liquidado (última cuota pagada, o saldo y mora en $0)
        $prestamo->finalizarSiLiquidado();

        $totalPagado  = (float)$request->monto;
        $carryMsg = '';
        if (isset($tipo) && $tipo === 'Parcial') {
            $pendienteCuota = round((float)$pago->monto_cuota - (float)$pago->monto_cobrado, 2);
            if ($pendienteCuota > 0) {
                $carryMsg = ' Queda pendiente $' . number_format($pendienteCuota, 2) . ' en esta cuota.';
            }
        }

        $adelantoMsg = $this->describirExcedente($excedenteInfo);

        $msg = 'Cuota #' . $pago->numero_pago . ' — $' . number_format($totalPagado, 2) . ' registrada.' . $carryMsg . $adelantoMsg;
        if ($prestamo->estatus === 'Finalizado') {
            $msg .= ' ✓ Préstamo finalizado.';
        }

        $quien    = Auth::user()->empleado?->nombre ?? Auth::user()->usuario;
        $tipoPago = isset($tipo) ? $tipo : ($montoRecibido > 0 ? ($montoRecibido >= (float)$pago->monto_cuota ? 'Pagado' : 'Parcial') : 'Solo mora');
        $descLog  = 'Cuota #' . $pago->numero_pago . ' registrada por ' . $quien .
                    ' — $' . number_format($totalPagado, 2) .
                    ' (' . strtolower($tipoPago) . ').';
        if ($pagoMora > 0)   $descLog .= ' Mora cobrada: $' . number_format($pagoMora, 2) . '.';
        if ($carryMsg)       $descLog .= $carryMsg;
        if ($adelantoMsg)    $descLog .= $adelantoMsg;
        if ($prestamo->estatus === 'Finalizado') $descLog .= ' ✓ Préstamo finalizado.';
        PrestamoActividad::log($prestamo->id, 'pago', $descLog, [
            'pago_id'         => $pago->id,
            'numero'          => $pago->numero_pago,
            'monto'           => $totalPagado,
            'mora'            => $pagoMora,
            'tipo'            => $tipoPago,
            'saldo'           => $prestamo->saldo_actual,
            'pendiente_cuota' => isset($pendienteCuota) ? $pendienteCuota : 0,
        ]);

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Admin / Promo: "Ponerse al día".
     * Cobra el atraso (cuotas vencidas + mora) y LIQUIDA las cuotas vencidas
     * de la más antigua a la más reciente, devolviendo el préstamo a estado
     * Activo con la próxima cuota futura como siguiente cobro.
     *
     * A diferencia de pagarCuota, no empuja excedentes a cuotas futuras: solo
     * salda lo que está fuera de la fecha acordada, evitando que el estado de la
     * cuenta quede mal interpretado.
     */
    public function ponerseAlDia(Request $request, $prestamoId)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'nota'  => 'nullable|string|max:255',
        ]);

        $user     = Auth::user();
        $empleado = $user->empleado;
        $adminId  = $user->adminId();
        $prestamo = Prestamo::where('id', $prestamoId)->where('admin_id', $adminId)->firstOrFail();

        if (!in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            return redirect()->back()->with('error', 'El préstamo no está activo.');
        }

        $hoy = now()->toDateString();

        // ── Poner la mora al día primero (igual que el resto del sistema) ────
        if ((float)$prestamo->interes_diario > 0
            && ($prestamo->interes_mora_activo || $prestamo->estatus === 'Atrasado')) {
            $desde = $prestamo->fecha_ultimo_interes ? $prestamo->fecha_ultimo_interes->toDateString() : $hoy;
            $dias  = (int) \Carbon\Carbon::parse($desde)->diffInDays($hoy);
            if ($dias > 0) {
                $prestamo->interes_acumulado    = round((float)$prestamo->interes_acumulado + ($dias * (float)$prestamo->interes_diario), 2);
                $prestamo->fecha_ultimo_interes = $hoy;
            }
        }

        // ── Cuotas vencidas (fecha pasada) aún sin saldar, más antiguas primero ──
        $vencidas = Pago::where('prestamo_id', $prestamoId)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->whereDate('fecha_programada', '<', $hoy)
            ->orderBy('fecha_programada')
            ->orderBy('numero_pago')
            ->get();

        $moraPendiente   = (float)$prestamo->interes_acumulado;
        $totalCuotasVenc = round($vencidas->sum(fn($p) => max(0, (float)$p->monto_cuota - (float)($p->monto_cobrado ?? 0))), 2);
        $totalAtraso     = round($totalCuotasVenc + $moraPendiente, 2);

        if ($vencidas->isEmpty() || $totalAtraso <= 0) {
            $prestamo->save(); // por si se actualizó la mora
            return redirect()->back()->with('error', 'Este préstamo no tiene cuotas atrasadas que cubrir.');
        }

        // Tope: "ponerse al día" no cobra de más; el excedente se ignora aquí
        // (para abonos por adelantado existe "Cobro inmediato").
        $montoRecibido = min((float)$request->monto, $totalAtraso);
        $nota          = trim((string)($request->nota ?? ''));

        DB::beginTransaction();
        try {
            // 1. Mora primero
            $pagoMora = 0.0;
            if ($moraPendiente > 0 && $montoRecibido > 0) {
                $pagoMora                    = min($montoRecibido, $moraPendiente);
                $prestamo->interes_acumulado = round($moraPendiente - $pagoMora, 2);
                $montoRecibido               = round($montoRecibido - $pagoMora, 2);
            }

            // 2. Cubrir cuotas vencidas, de la más antigua a la más reciente
            $cuotasLiquidadas = 0;
            $aplicadoCuotas   = 0.0;
            foreach ($vencidas as $cuota) {
                if ($montoRecibido <= 0.005) break;

                $yaCobrado = (float)($cuota->monto_cobrado ?? 0);
                $restante  = round((float)$cuota->monto_cuota - $yaCobrado, 2);
                if ($restante <= 0) continue;

                if ($montoRecibido >= $restante - 0.005) {
                    // Cuota saldada por completo
                    $cuota->monto_cobrado = (float)$cuota->monto_cuota;
                    $cuota->tipo_cobro    = 'completo';
                    $cuota->estatus       = 'Pagado';
                    $cuota->fecha_pago    = $hoy;
                    $cuota->cobrador_id   = $empleado?->id;
                    $cuota->nota_cobro    = trim('Ponerse al día' . ($nota ? ' — ' . $nota : ''));
                    $cuota->save();

                    $montoRecibido   = round($montoRecibido - $restante, 2);
                    $aplicadoCuotas += $restante;
                    $cuotasLiquidadas++;
                } else {
                    // Abono parcial sobre la cuota vencida más antigua restante
                    $cuota->monto_cobrado = round($yaCobrado + $montoRecibido, 2);
                    $cuota->tipo_cobro    = 'parcial';
                    $cuota->estatus       = 'Parcial';
                    $cuota->fecha_pago    = $hoy;
                    $cuota->cobrador_id   = $empleado?->id;
                    $cuota->nota_cobro    = trim('Ponerse al día (parcial)' . ($nota ? ' — ' . $nota : ''));
                    $cuota->save();

                    $aplicadoCuotas += $montoRecibido;
                    $montoRecibido   = 0;
                    break;
                }
            }

            // 3. Reducir el saldo por lo aplicado a cuotas (la mora va aparte)
            if ($aplicadoCuotas > 0) {
                $prestamo->saldo_actual = max(0, round((float)$prestamo->saldo_actual - $aplicadoCuotas, 2));
            }

            // 4. Estado autoritativo según si AÚN quedan cuotas fuera de fecha.
            //    Corrige también préstamos mal etiquetados (Activo con vencidas, o
            //    Atrasado ya al corriente). El hook del modelo registra el cambio.
            $quedanVencidas = Pago::where('prestamo_id', $prestamoId)
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->whereDate('fecha_programada', '<', $hoy)
                ->exists();
            if ($quedanVencidas && $prestamo->estatus === 'Activo') {
                $prestamo->estatus = 'Atrasado';
            } elseif (!$quedanVencidas && $prestamo->estatus === 'Atrasado') {
                $prestamo->estatus = 'Activo';
            }

            $prestamo->save();

            // Si con esto quedó todo liquidado, finalizar
            $prestamo->finalizarSiLiquidado();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->back()->with('error', 'No se pudo registrar el pago de atraso. No se realizó ningún cambio.');
        }

        $totalAplicado = round($aplicadoCuotas + $pagoMora, 2);
        $quien         = $empleado?->nombre ?? $user->usuario;

        $desc = 'Ponerse al día por ' . $quien . ': $' . number_format($totalAplicado, 2) .
                ' — ' . $cuotasLiquidadas . ' cuota(s) atrasada(s) cubierta(s)' .
                ($pagoMora > 0 ? ', mora $' . number_format($pagoMora, 2) : '') . '.';
        if ($prestamo->estatus === 'Finalizado') {
            $desc .= ' ✓ Préstamo finalizado.';
        } elseif (!$quedanVencidas) {
            $desc .= ' Préstamo al corriente.';
        }
        PrestamoActividad::log($prestamo->id, 'pago', $desc, [
            'tipo'              => 'ponerse_al_dia',
            'monto'             => $totalAplicado,
            'mora'              => $pagoMora,
            'cuotas_liquidadas' => $cuotasLiquidadas,
            'saldo'             => $prestamo->saldo_actual,
            'cobrador_id'       => $empleado?->id,
        ]);

        $msg = 'Atraso cobrado: $' . number_format($totalAplicado, 2) . '. ' .
               $cuotasLiquidadas . ' cuota(s) liquidada(s).';
        if ($prestamo->estatus === 'Finalizado')  $msg .= ' El préstamo quedó finalizado.';
        elseif (!$quedanVencidas)                  $msg .= ' El préstamo está al corriente.';
        else                                       $msg .= ' Aún quedan cuotas atrasadas por cubrir.';

        return redirect()->back()->with('success', $msg);
    }
}
