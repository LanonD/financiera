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

        if (in_array($puesto, ['collector', 'promo']) && $empleado) {
            // Collector ve sus préstamos asignados; promo ve los suyos donde se asignó como cobrador
            $prestamos = Prestamo::with(['cliente'])
                ->where('cobrador_id', $empleado->id)
                ->whereIn('estatus', ['Activo', 'Atrasado'])
                ->get()
                ->map(function ($p) {
                    $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado'])->orderBy('fecha_programada')->first();
                    $p->proximo_pago = $next?->fecha_programada;
                    if ($next && $next->fecha_programada < now()->toDateString()) {
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
                ->map(function ($p) {
                    $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado'])->orderBy('fecha_programada')->first();
                    $p->proximo_pago = $next?->fecha_programada;
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
            $p->proximo_pago = $next?->fecha_programada;
            $p->dias_atraso  = ($next && $next->fecha_programada < now()->toDateString())
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

        $cobradores = Empleado::where('puesto', 'collector')->where('activo', true)->get();

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
     */
    public function registrar(Request $request)
    {
        $user     = Auth::user();
        $empleado = $user->empleado;

        $cobros = $request->json()->all(); // { prestamoId: {tipo, monto, nota} }

        $registrados = 0;
        $errors = [];

        foreach ($cobros as $prestamoId => $cobro) {
            $prestamo = Prestamo::find($prestamoId);
            if (!$prestamo) { $errors[] = "Préstamo #{$prestamoId} no encontrado"; continue; }

            // Get next pending payment
            $pago = Pago::where('prestamo_id', $prestamoId)
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->orderBy('numero_pago')
                ->first();

            if (!$pago) { $errors[] = "Sin pago pendiente en préstamo #{$prestamoId}"; continue; }

            $monto = (float)($cobro['monto'] ?? 0);
            if ($monto <= 0) continue;

            $tipo  = $monto >= $pago->monto_cuota ? 'Pagado' : 'Parcial';
            $nota  = $cobro['nota'] ?? null;

            // Update payment record
            $pago->monto_cobrado = $monto;
            $pago->tipo_cobro    = $tipo === 'Pagado' ? 'completo' : 'parcial';
            $pago->nota_cobro    = $nota;
            $pago->fecha_pago    = now()->toDateString();
            $pago->estatus       = $tipo;
            $pago->cobrador_id   = $empleado?->id;
            $pago->save();

            // Update loan balance
            if ($tipo === 'Pagado') {
                $prestamo->saldo_actual = max(0, $prestamo->saldo_actual - $pago->capital);
                // Check if all paid
                $remaining = Pago::where('prestamo_id', $prestamoId)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                    ->count();
                if ($remaining === 0) {
                    $prestamo->estatus = 'Finalizado';
                } else {
                    $prestamo->estatus = 'Activo';
                }
                $prestamo->save();
            }

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
