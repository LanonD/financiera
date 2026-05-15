<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\Empleado;
use Illuminate\Support\Facades\Auth;

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
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->where('fecha_programada', '<', now()->toDateString())
                ->orderBy('fecha_programada')
                ->first();

            if ($primerVencido) {
                $changed = false;
                if ($p->estatus === 'Activo') { $p->estatus = 'Atrasado'; $changed = true; }
                if ((float)$p->interes_diario == 0) { $p->interes_diario = 10.00; $changed = true; }
                if (!$p->interes_mora_activo) { $p->interes_mora_activo = true; $changed = true; }
                if (!$p->fecha_ultimo_interes) {
                    $p->fecha_ultimo_interes = $primerVencido->fecha_programada->toDateString();
                    $changed = true;
                }
                if ($changed) $p->save();
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
                    $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado'])->orderBy('fecha_programada')->first();
                    // toDateString() evita que Carbon genere '2026-04-15 00:00:00' que rompe comparaciones de string
                    $p->proximo_pago = $next?->fecha_programada?->toDateString();
                    if ($next && $p->proximo_pago < now()->toDateString()) {
                        $p->dias_atraso = now()->diffInDays($next->fecha_programada);
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
                    $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado'])->orderBy('fecha_programada')->first();
                    $p->proximo_pago = $next?->fecha_programada?->toDateString();
                    $p->dias_atraso  = 0;
                    return $p;
                });
            $cobrador = $empleado;
        }

        return view('collector.cobros', compact('prestamos', 'cobrador', 'puesto'));
    }

    /**
     * Admin: assign collectors to loans
     */
    public function asignar(Request $request)
    {
        $adminId          = auth()->user()->adminId();
        $filtroDesde      = $request->query('desde', '');
        $filtroHasta      = $request->query('hasta', '');
        $filtroSinCobrador= (bool)$request->query('sin_cobrador', false);
        $filtroBusqueda   = $request->query('busqueda', '');

        $query = Prestamo::with(['cliente', 'cobrador'])
            ->where('admin_id', $adminId)
            ->whereIn('estatus', ['Activo', 'Atrasado']);

        if ($filtroSinCobrador) {
            $query->whereNull('cobrador_id');
        }
        if ($filtroBusqueda) {
            $query->whereHas('cliente', fn($q) => $q->where('nombre', 'like', "%{$filtroBusqueda}%"));
        }

        $prestamos = $query->get()->map(function ($p) {
            $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado'])->orderBy('fecha_programada')->first();
            $p->proximo_pago = $next?->fecha_programada?->toDateString();
            $p->dias_atraso  = ($next && $p->proximo_pago < now()->toDateString())
                ? now()->diffInDays($next->fecha_programada)
                : 0;
            // Check if paid today
            $pagadoHoy = $p->pagos()
                ->whereIn('estatus', ['Pagado', 'Parcial'])
                ->whereDate('fecha_pago', now()->toDateString())
                ->first();
            $p->pagado_hoy    = $pagadoHoy ? 1 : 0;
            $p->tipo_pago_hoy = $pagadoHoy?->estatus;
            return $p;
        });

        if ($filtroDesde || $filtroHasta) {
            $prestamos = $prestamos->filter(function ($p) use ($filtroDesde, $filtroHasta) {
                $pp = $p->proximo_pago;
                if (!$pp && $p->dias_atraso <= 0) return false;
                if ($filtroDesde && $pp && $pp < $filtroDesde && $p->dias_atraso <= 0) return false;
                if ($filtroHasta && $pp && $pp > $filtroHasta) return false;
                return true;
            });
        }

        // Include multi-role employees that have 'collector' among their roles (scoped to admin)
        $cobradores = Empleado::where('activo', true)
            ->where('admin_id', $adminId)
            ->get()
            ->filter(fn($e) => $e->hasRole('collector'))
            ->values();

        return view('admin.cobros_asignar', compact('prestamos', 'cobradores', 'filtroDesde', 'filtroHasta', 'filtroSinCobrador', 'filtroBusqueda'));
    }

    /**
     * Admin: save collector assignments
     */
    public function guardarAsignacion(Request $request)
    {
        $asignaciones = $request->input('asignacion', []);
        $guardados = 0;

        foreach ($asignaciones as $prestamoId => $cobradorId) {
            $prestamo = Prestamo::find($prestamoId);
            if (!$prestamo) continue;
            $prestamo->cobrador_id = $cobradorId > 0 ? $cobradorId : null;
            $prestamo->save();
            // Also assign to pending pagos
            if ($cobradorId > 0) {
                Pago::where('prestamo_id', $prestamoId)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                    ->update(['cobrador_id' => $cobradorId]);
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

        $cobros = $request->json()->all(); // { prestamoId: {tipo, monto, nota} }

        $registrados = 0;
        $errors      = [];

        foreach ($cobros as $prestamoId => $cobro) {
            $prestamo = Prestamo::find($prestamoId);
            if (!$prestamo) { $errors[] = "Préstamo #{$prestamoId} no encontrado"; continue; }

            // ── 1. Auto-activate mora and bring interest up to date ─────────────
            if (in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
                $primerVencido = Pago::where('prestamo_id', $prestamoId)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                    ->where('fecha_programada', '<', now()->toDateString())
                    ->orderBy('fecha_programada')
                    ->first();
                if ($primerVencido) {
                    if ($prestamo->estatus === 'Activo') $prestamo->estatus = 'Atrasado';
                    if ((float)$prestamo->interes_diario == 0) $prestamo->interes_diario = 10.00;
                    if (!$prestamo->interes_mora_activo) $prestamo->interes_mora_activo = true;
                    if (!$prestamo->fecha_ultimo_interes)
                        $prestamo->fecha_ultimo_interes = $primerVencido->fecha_programada->toDateString();
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
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
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

            // ── 4. Apply remainder to cuota (interest first, then capital) ──────
            if ($montoRecibido > 0) {
                $interesDelPago  = (float)$pago->interes;
                $capitalDelPago  = (float)$pago->capital;

                // Interest is always collected before principal
                $interesACobrar  = min($montoRecibido, $interesDelPago);
                $restanteTrasInt = $montoRecibido - $interesACobrar;
                $capitalACobrar  = min($restanteTrasInt, $capitalDelPago);

                $tipo = $montoRecibido >= $pago->monto_cuota ? 'Pagado' : 'Parcial';

                $pago->monto_cobrado = $montoRecibido;
                $pago->tipo_cobro    = $tipo === 'Pagado' ? 'completo' : 'parcial';
                $pago->nota_cobro    = $nota;
                $pago->fecha_pago    = now()->toDateString();
                $pago->estatus       = $tipo;
                $pago->cobrador_id   = $empleado?->id;
                $pago->save();

                // Reduce saldo by capital actually collected (even on partial)
                if ($capitalACobrar > 0) {
                    $prestamo->saldo_actual = max(0, round((float)$prestamo->saldo_actual - $capitalACobrar, 2));
                }

                if ($tipo === 'Pagado') {
                    $remaining = Pago::where('prestamo_id', $prestamoId)
                        ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                        ->count();
                    if ($remaining === 0) {
                        $prestamo->estatus             = 'Finalizado';
                        $prestamo->interes_mora_activo = false;
                        $prestamo->interes_diario      = 0;
                    } else {
                        $prestamo->estatus = 'Activo';
                    }
                }
            } elseif ($pagoMora > 0) {
                // Payment covered only mora (nothing left for cuota)
                // Note it on the pago without changing its estatus
                $pago->nota_cobro = ($pago->nota_cobro ? $pago->nota_cobro . ' | ' : '') . $nota;
                $pago->save();
            }

            $prestamo->save();
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
        $prestamo = Prestamo::findOrFail($id);

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

        // ── 2. Interest of next pending cuota before touching capital ────────
        $pagoInteres = 0;
        if ($monto > 0) {
            $proximaCuota = Pago::where('prestamo_id', $prestamo->id)
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->where(fn($q) => $q->whereNull('tipo_pago')->orWhere('tipo_pago', 'plan'))
                ->orderBy('numero_pago')
                ->first();

            if ($proximaCuota && (float)$proximaCuota->interes > 0) {
                $pagoInteres = min($monto, (float)$proximaCuota->interes);
                $monto      -= $pagoInteres;
                $nota       .= ' | Interés: $' . number_format($pagoInteres, 2);

                // Reduce the pending cuota's interest and monto_cuota so that
                // $interesRestante and the collector's view reflect the true balance
                $proximaCuota->interes     = round((float)$proximaCuota->interes     - $pagoInteres, 2);
                $proximaCuota->monto_cuota = round((float)$proximaCuota->monto_cuota - $pagoInteres, 2);
                $proximaCuota->save();
            }
        }

        // ── 3. Remainder reduces capital (saldo_actual) ──────────────────────
        $capitalPagado = 0;
        if ($monto > 0) {
            $capitalPagado          = $monto;
            $prestamo->saldo_actual = max(0, round((float)$prestamo->saldo_actual - $capitalPagado, 2));
        }

        // Auto-finalize: if both capital and mora reach 0, the loan is fully paid
        $mensajeFin = '';
        if ((float)$prestamo->saldo_actual <= 0 && (float)$prestamo->interes_acumulado <= 0) {
            $prestamo->estatus             = 'Finalizado';
            $prestamo->payment_hold        = false;
            $prestamo->interes_mora_activo = false;
            $prestamo->interes_diario      = 0;

            // Mark remaining pending plan pagos as liquidated (paid early, $0)
            Pago::where('prestamo_id', $prestamo->id)
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->where('tipo_pago', 'plan')
                ->update([
                    'estatus'       => 'Pagado',
                    'monto_cobrado' => 0,
                    'tipo_cobro'    => 'completo',
                    'tipo_pago'     => 'liquidado',
                    'fecha_pago'    => now()->toDateString(),
                    'nota_cobro'    => 'Liquidación anticipada',
                ]);

            // Also cancel scheduled (agendado) pending pagos
            Pago::where('prestamo_id', $prestamo->id)
                ->whereIn('estatus', ['Pendiente'])
                ->where('tipo_pago', 'agendado')
                ->update([
                    'estatus'    => 'Pagado',
                    'monto_cobrado' => 0,
                    'tipo_cobro' => 'completo',
                    'tipo_pago'  => 'liquidado',
                    'fecha_pago' => now()->toDateString(),
                    'nota_cobro' => 'Cancelado - préstamo liquidado',
                ]);

            $mensajeFin = ' ✓ Préstamo finalizado por liquidación anticipada.';
        }

        $maxNumero = Pago::where('prestamo_id', $prestamo->id)->max('numero_pago') ?? 0;

        Pago::create([
            'prestamo_id'      => $prestamo->id,
            'cobrador_id'      => $empleado?->id,
            'numero_pago'      => $maxNumero + 1,
            'monto_cuota'      => $montoTotal,
            'interes'          => round($pagoMora + $pagoInteres, 2),
            'capital'          => round($capitalPagado, 2),
            'saldo_restante'   => $prestamo->saldo_actual,
            'monto_cobrado'    => $montoTotal,
            'tipo_cobro'       => 'completo',
            'tipo_pago'        => 'extra',
            'nota_cobro'       => $nota,
            'fecha_programada' => now()->toDateString(),
            'fecha_pago'       => now()->toDateString(),
            'estatus'          => 'Pagado',
        ]);

        $prestamo->save();

        return redirect()->back()->with('success', 'Cobro inmediato de $' . number_format($montoTotal, 2) . ' registrado.' . $mensajeFin);
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

        $prestamo = Prestamo::findOrFail($id);

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
        $prestamo = Prestamo::findOrFail($id);

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
                    ->whereIn('estatus', ['Pendiente', 'Atrasado'])
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

            return redirect()->back()->with('success', 'Pago diferido cancelado. El plan de pagos fue restaurado.');
        }

        // ── Activate hold ────────────────────────────────────────────────────
        $planPagos = Pago::where('prestamo_id', $id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado'])
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

        $prestamo = Prestamo::findOrFail($id);

        // Promo solo puede asignarse a sus propios préstamos
        if ($user->puesto === 'promo' && $prestamo->promotor_id !== $empleado->id) {
            abort(403, 'Solo puedes asignarte a tus propios préstamos.');
        }

        $prestamo->cobrador_id = $empleado->id;
        $prestamo->save();

        // Propagar la asignación a los pagos pendientes
        Pago::where('prestamo_id', $id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado'])
            ->update(['cobrador_id' => $empleado->id]);

        return redirect()->back()->with('success', 'Te has asignado como cobrador de este préstamo.');
    }

    /**
     * Admin / Promo: register a payment for a specific cuota (pago) selected by the user.
     * Applies mora first, then the remainder to the chosen cuota.
     */
    public function pagarCuota(Request $request, $prestamoId)
    {
        $request->validate([
            'pago_id' => 'required|integer|exists:pagos,id',
            'monto'   => 'required|numeric|min:0.01',
            'nota'    => 'nullable|string|max:255',
        ]);

        $user     = Auth::user();
        $empleado = $user->empleado;
        $prestamo = Prestamo::findOrFail($prestamoId);

        // Verify the pago belongs to this prestamo and is payable
        $pago = Pago::where('id', $request->pago_id)
            ->where('prestamo_id', $prestamoId)
            ->whereIn('estatus', ['Pendiente', 'Atrasado'])
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
        if ($montoRecibido > 0) {
            $interesACobrar = min($montoRecibido, (float)$pago->interes);
            $restante       = $montoRecibido - $interesACobrar;
            $capitalACobrar = min($restante, (float)$pago->capital);

            $tipo = $montoRecibido >= (float)$pago->monto_cuota ? 'Pagado' : 'Parcial';

            $pago->monto_cobrado = $montoRecibido;
            $pago->tipo_cobro    = $tipo === 'Pagado' ? 'completo' : 'parcial';
            $pago->nota_cobro    = $nota ?: null;
            $pago->fecha_pago    = now()->toDateString();
            $pago->estatus       = $tipo;
            $pago->cobrador_id   = $empleado?->id;
            $pago->save();

            if ($capitalACobrar > 0) {
                $prestamo->saldo_actual = max(0, round((float)$prestamo->saldo_actual - $capitalACobrar, 2));
            }

            // Check if all payments are now done
            if ($tipo === 'Pagado') {
                $remaining = Pago::where('prestamo_id', $prestamoId)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                    ->count();
                if ($remaining === 0) {
                    $prestamo->estatus             = 'Finalizado';
                    $prestamo->interes_mora_activo = false;
                    $prestamo->interes_diario      = 0;
                } else {
                    $prestamo->estatus = 'Activo';
                }
            }
        } elseif ($pagoMora > 0) {
            // Payment covered only mora, annotate pago but keep it pending
            $pago->nota_cobro = ($pago->nota_cobro ? $pago->nota_cobro . ' | ' : '') . $nota;
            $pago->save();
        }

        $prestamo->save();

        $totalPagado = (float)$request->monto;
        $msg = 'Cuota #' . $pago->numero_pago . ' — $' . number_format($totalPagado, 2) . ' registrada.';
        if ($prestamo->estatus === 'Finalizado') {
            $msg .= ' ✓ Préstamo finalizado.';
        }

        return redirect()->back()->with('success', $msg);
    }
}
