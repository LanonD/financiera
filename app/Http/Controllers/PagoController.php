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
        $puesto   = $user->puesto;
        $empleado = $user->empleado;

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

        if (in_array($puesto, ['collector', 'promo']) && $empleado) {
            // Collector ve sus préstamos asignados; promo ve los suyos donde se asignó como cobrador
            $prestamos = Prestamo::with(['cliente'])
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
            // Admin sees all
            $prestamos = Prestamo::with(['cliente', 'cobrador'])
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
        $filtroDesde      = $request->query('desde', '');
        $filtroHasta      = $request->query('hasta', '');
        $filtroSinCobrador= (bool)$request->query('sin_cobrador', false);
        $filtroBusqueda   = $request->query('busqueda', '');

        $query = Prestamo::with(['cliente', 'cobrador'])
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

        // Include multi-role employees that have 'collector' among their roles
        $cobradores = Empleado::where('activo', true)->get()->filter(fn($e) => $e->hasRole('collector'))->values();

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
                    $prestamo->estatus = $remaining === 0 ? 'Finalizado' : 'Activo';
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
}
