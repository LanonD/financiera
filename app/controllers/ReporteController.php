<?php
require_once ROOT_PATH . '/app/models/Loan.php';

class ReporteController {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function index(): void {
        $hoy = date('Y-m-d');

        // ── Filtros de fecha ─────────────────────────────────────────
        $fecha_desde = $_GET['desde'] ?? date('Y-m-01');
        $fecha_hasta = $_GET['hasta'] ?? $hoy;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) $fecha_desde = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) $fecha_hasta = $hoy;
        if ($fecha_desde > $fecha_hasta) [$fecha_desde, $fecha_hasta] = [$fecha_hasta, $fecha_desde];

        $desde = $this->db->real_escape_string($fecha_desde);
        $hasta = $this->db->real_escape_string($fecha_hasta);

        // ── Resumen cobros en el rango ───────────────────────────────
        $resumen = $this->db->query("
            SELECT
                COUNT(*)                                                                  AS total_cobros,
                COALESCE(SUM(monto_cobrado), 0)                                           AS total_monto,
                COALESCE(SUM(capital), 0)                                                 AS total_capital,
                COALESCE(SUM(interes), 0)                                                 AS total_interes,
                SUM(CASE WHEN DATE(fecha_pago) <= fecha_programada THEN 1   ELSE 0 END)  AS a_tiempo_num,
                COALESCE(SUM(CASE WHEN DATE(fecha_pago) <= fecha_programada THEN monto_cobrado ELSE 0 END), 0) AS a_tiempo_monto,
                SUM(CASE WHEN DATE(fecha_pago) > fecha_programada THEN 1    ELSE 0 END)  AS tarde_num,
                COALESCE(SUM(CASE WHEN DATE(fecha_pago) > fecha_programada  THEN monto_cobrado ELSE 0 END), 0) AS tarde_monto
            FROM pagos
            WHERE estatus IN ('Pagado','Parcial')
              AND DATE(fecha_pago) BETWEEN '$desde' AND '$hasta'
        ")->fetch_assoc();

        // ── Cobros del día (hoy) ─────────────────────────────────────
        $cobros_hoy = $this->db->query("
            SELECT COUNT(*) AS num, COALESCE(SUM(monto_cobrado),0) AS total
            FROM pagos
            WHERE DATE(fecha_pago) = '$hoy' AND estatus IN ('Pagado','Parcial')
        ")->fetch_assoc();

        // ── Desembolsos del día (hoy) ────────────────────────────────
        $desembolsos_hoy = $this->db->query("
            SELECT COUNT(*) AS num, COALESCE(SUM(monto_entregado),0) AS total
            FROM prestamos
            WHERE DATE(fecha_entrega) = '$hoy'
        ")->fetch_assoc();

        // ── Cartera total activa ─────────────────────────────────────
        $cartera = $this->db->query("
            SELECT
                COUNT(*)                                          AS num_prestamos,
                COALESCE(SUM(saldo_actual),0)                     AS saldo_total,
                COALESCE(SUM(interes_acumulado),0)                AS interes_total,
                COALESCE(SUM(saldo_actual + interes_acumulado),0) AS deuda_total
            FROM prestamos
            WHERE estatus IN ('Activo','Atrasado')
        ")->fetch_assoc();

        // ── Cobros por día en el rango (gráfica) ─────────────────────
        $cobros_rango = $this->db->query("
            SELECT
                DATE(fecha_pago)            AS dia,
                COALESCE(SUM(monto_cobrado),0) AS total,
                COALESCE(SUM(capital),0)       AS principal,
                COALESCE(SUM(interes),0)       AS interes_dia
            FROM pagos
            WHERE estatus IN ('Pagado','Parcial')
              AND DATE(fecha_pago) BETWEEN '$desde' AND '$hasta'
            GROUP BY DATE(fecha_pago)
            ORDER BY dia ASC
        ")->fetch_all(MYSQLI_ASSOC);

        // ── Cobros por cobrador en el rango ──────────────────────────
        $cobros_por_cobrador = $this->db->query("
            SELECT
                e.nombre,
                COUNT(*)                    AS num,
                COALESCE(SUM(pg.monto_cobrado),0) AS total,
                COALESCE(SUM(pg.capital),0)        AS principal,
                COALESCE(SUM(pg.interes),0)        AS interes_cobrador,
                SUM(CASE WHEN DATE(pg.fecha_pago) <= pg.fecha_programada THEN 1 ELSE 0 END) AS a_tiempo
            FROM pagos pg
            JOIN empleados e ON pg.cobrador_id = e.id
            WHERE DATE(pg.fecha_pago) BETWEEN '$desde' AND '$hasta'
              AND pg.estatus IN ('Pagado','Parcial')
            GROUP BY e.id, e.nombre
            ORDER BY total DESC
        ")->fetch_all(MYSQLI_ASSOC);

        // ── Préstamos por estatus ────────────────────────────────────
        $por_estatus = $this->db->query("
            SELECT estatus, COUNT(*) AS num, COALESCE(SUM(saldo_actual),0) AS saldo
            FROM prestamos
            GROUP BY estatus
            ORDER BY FIELD(estatus,'Activo','Atrasado','Pendiente','Finalizado','Retirado','Cancelado')
        ")->fetch_all(MYSQLI_ASSOC);

        // ── Préstamos atrasados (top 10) ─────────────────────────────
        $atrasados = $this->db->query("
            SELECT p.id, c.nombre AS cliente, p.saldo_actual, p.interes_acumulado,
                   MIN(pg.fecha_programada) AS vencimiento,
                   DATEDIFF(CURDATE(), MIN(pg.fecha_programada)) AS dias_atraso
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            LEFT JOIN pagos pg ON pg.prestamo_id = p.id AND pg.estatus IN ('Pendiente','Atrasado')
            WHERE p.estatus = 'Atrasado'
            GROUP BY p.id, c.nombre, p.saldo_actual, p.interes_acumulado
            ORDER BY dias_atraso DESC
            LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);

        $pageTitle  = 'Reportes';
        $breadcrumb = 'Administración · Reportes';

        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/reportes.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }
}
