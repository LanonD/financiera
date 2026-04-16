<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $fecha_hasta = $request->query('hasta', now()->toDateString());
        $fecha_desde = $request->query('desde', now()->startOfMonth()->toDateString());

        // ── Resumen del período ──────────────────────────────────────────────
        $resumen = Pago::whereBetween('fecha_pago', [$fecha_desde, $fecha_hasta])
            ->whereIn('estatus', ['Pagado', 'Parcial'])
            ->selectRaw("
                COUNT(*) as total_cobros,
                COALESCE(SUM(monto_cobrado), 0) as total_monto,
                COALESCE(SUM(capital), 0) as total_capital,
                COALESCE(SUM(interes), 0) as total_interes,
                COALESCE(SUM(CASE WHEN fecha_pago <= fecha_programada THEN 1 ELSE 0 END), 0) as a_tiempo_num,
                COALESCE(SUM(CASE WHEN fecha_pago > fecha_programada THEN 1 ELSE 0 END), 0) as tarde_num,
                COALESCE(SUM(CASE WHEN fecha_pago <= fecha_programada THEN monto_cobrado ELSE 0 END), 0) as a_tiempo_monto,
                COALESCE(SUM(CASE WHEN fecha_pago > fecha_programada THEN monto_cobrado ELSE 0 END), 0) as tarde_monto
            ")->first();

        // ── Cartera activa ───────────────────────────────────────────────────
        $cartera = Prestamo::whereIn('estatus', ['Activo', 'Atrasado'])
            ->selectRaw("
                COUNT(*) as num_prestamos,
                COALESCE(SUM(saldo_actual), 0) as saldo_total,
                COALESCE(SUM(interes_acumulado), 0) as interes_total,
                COALESCE(SUM(saldo_actual + interes_acumulado), 0) as deuda_total
            ")->first();

        // ── Actividad de hoy ─────────────────────────────────────────────────
        $hoy = now()->toDateString();

        $cobros_hoy = Pago::whereDate('fecha_pago', $hoy)
            ->whereIn('estatus', ['Pagado', 'Parcial'])
            ->selectRaw("COUNT(*) as num, COALESCE(SUM(monto_cobrado),0) as total")
            ->first();

        $desembolsos_hoy = Prestamo::whereDate('fecha_entrega', $hoy)
            ->whereNotNull('fecha_entrega')
            ->selectRaw("COUNT(*) as num, COALESCE(SUM(monto_entregado),0) as total")
            ->first();

        // ── Dinero enviado en el período ─────────────────────────────────────
        // Usa fecha_inicio como referencia de cuándo salió el dinero a la calle
        $enviado_rango = Prestamo::whereBetween('fecha_inicio', [$fecha_desde, $fecha_hasta])
            ->selectRaw("
                COUNT(*) as num_prestamos,
                COALESCE(SUM(monto_entregado), 0) as total_enviado,
                COALESCE(SUM(monto), 0) as total_acordado,
                COALESCE(SUM(monto - monto_entregado), 0) as ganancia_esperada
            ")->first();

        // ── Enviados por día (para el chart) ─────────────────────────────────
        $enviados_por_dia = Prestamo::whereBetween('fecha_inicio', [$fecha_desde, $fecha_hasta])
            ->selectRaw("DATE(fecha_inicio) as dia, COALESCE(SUM(monto_entregado),0) as total_enviado")
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        // ── Cobros por día en rango ──────────────────────────────────────────
        $cobros_rango = Pago::whereBetween('fecha_pago', [$fecha_desde, $fecha_hasta])
            ->whereIn('estatus', ['Pagado', 'Parcial'])
            ->selectRaw("
                DATE(fecha_pago) as dia,
                COALESCE(SUM(monto_cobrado),0) as total,
                COALESCE(SUM(capital),0) as principal,
                COALESCE(SUM(interes),0) as interes_dia
            ")
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        // ── Cobros por cobrador ──────────────────────────────────────────────
        $cobros_por_cobrador = Pago::whereBetween('fecha_pago', [$fecha_desde, $fecha_hasta])
            ->whereIn('estatus', ['Pagado', 'Parcial'])
            ->whereNotNull('cobrador_id')
            ->join('empleados', 'pagos.cobrador_id', '=', 'empleados.id')
            ->selectRaw("
                empleados.nombre,
                COUNT(*) as num,
                COALESCE(SUM(pagos.monto_cobrado),0) as total,
                COALESCE(SUM(pagos.capital),0) as principal,
                COALESCE(SUM(pagos.interes),0) as interes_cobrador,
                COALESCE(SUM(CASE WHEN pagos.fecha_pago <= pagos.fecha_programada THEN 1 ELSE 0 END),0) as a_tiempo
            ")
            ->groupBy('empleados.id', 'empleados.nombre')
            ->orderByDesc('total')
            ->get();

        // ── Préstamos por estatus ────────────────────────────────────────────
        $por_estatus = Prestamo::selectRaw("
                estatus,
                COUNT(*) as num,
                COALESCE(SUM(saldo_actual),0) as saldo
            ")
            ->groupBy('estatus')
            ->orderByRaw("FIELD(estatus,'Activo','Atrasado','Pendiente','Finalizado','Cancelado','Retirado')")
            ->get();

        // ── Top 10 atrasados ─────────────────────────────────────────────────
        $atrasados = Prestamo::with('cliente')
            ->where('prestamos.estatus', 'Atrasado')
            ->join('pagos as p2', function ($j) {
                $j->on('prestamos.id', '=', 'p2.prestamo_id')
                  ->whereIn('p2.estatus', ['Pendiente', 'Atrasado'])
                  ->whereRaw("p2.numero_pago = (SELECT MIN(numero_pago) FROM pagos WHERE prestamo_id = prestamos.id AND pagos.estatus IN ('Pendiente','Atrasado'))");
            })
            ->select('prestamos.*') // Ensure we select only prestamos columns to avoid collision with p2
            ->selectRaw("DATEDIFF(CURDATE(), p2.fecha_programada) as dias_atraso")
            ->orderByDesc('dias_atraso')
            ->limit(10)
            ->get();

        return view('admin.reportes', compact(
            'resumen', 'cartera', 'cobros_hoy', 'desembolsos_hoy',
            'enviado_rango', 'enviados_por_dia',
            'cobros_rango', 'cobros_por_cobrador', 'por_estatus', 'atrasados',
            'fecha_desde', 'fecha_hasta'
        ));
    }
}

