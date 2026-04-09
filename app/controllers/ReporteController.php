<?php
require_once ROOT_PATH . '/app/models/Loan.php';

class ReporteController {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function index(): void {
        $hoy = date('Y-m-d');

        // ── Desembolsos del día ──────────────────────────────────────
        $r = $this->db->query("
            SELECT COUNT(*) AS num, COALESCE(SUM(monto_entregado),0) AS total
            FROM prestamos
            WHERE DATE(fecha_entrega) = '$hoy'
        ")->fetch_assoc();
        $desembolsos_hoy = $r;

        // ── Cobros del día ───────────────────────────────────────────
        $r = $this->db->query("
            SELECT COUNT(*) AS num, COALESCE(SUM(monto_cobrado),0) AS total
            FROM pagos
            WHERE DATE(fecha_pago) = '$hoy' AND estatus IN ('Pagado','Parcial')
        ")->fetch_assoc();
        $cobros_hoy = $r;

        // ── Cartera total activa ─────────────────────────────────────
        $r = $this->db->query("
            SELECT
                COUNT(*)                              AS num_prestamos,
                COALESCE(SUM(saldo_actual),0)         AS saldo_total,
                COALESCE(SUM(interes_acumulado),0)    AS interes_total,
                COALESCE(SUM(saldo_actual + interes_acumulado),0) AS deuda_total
            FROM prestamos
            WHERE estatus IN ('Activo','Atrasado')
        ")->fetch_assoc();
        $cartera = $r;

        // ── Préstamos por estatus ────────────────────────────────────
        $por_estatus = $this->db->query("
            SELECT estatus, COUNT(*) AS num, COALESCE(SUM(saldo_actual),0) AS saldo
            FROM prestamos
            GROUP BY estatus
            ORDER BY FIELD(estatus,'Activo','Atrasado','Pendiente','Finalizado','Retirado','Cancelado')
        ")->fetch_all(MYSQLI_ASSOC);

        // ── Cobros del día por cobrador ──────────────────────────────
        $cobros_por_cobrador = $this->db->query("
            SELECT e.nombre, COUNT(*) AS num, COALESCE(SUM(pg.monto_cobrado),0) AS total
            FROM pagos pg
            JOIN empleados e ON pg.cobrador_id = e.id
            WHERE DATE(pg.fecha_pago) = '$hoy' AND pg.estatus IN ('Pagado','Parcial')
            GROUP BY e.id, e.nombre
            ORDER BY total DESC
        ")->fetch_all(MYSQLI_ASSOC);

        // ── Últimos 7 días de cobros (gráfica) ───────────────────────
        $cobros_7dias = $this->db->query("
            SELECT DATE(fecha_pago) AS dia, COALESCE(SUM(monto_cobrado),0) AS total
            FROM pagos
            WHERE fecha_pago >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND estatus IN ('Pagado','Parcial')
            GROUP BY DATE(fecha_pago)
            ORDER BY dia ASC
        ")->fetch_all(MYSQLI_ASSOC);

        // ── Préstamos atrasados ──────────────────────────────────────
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
        $breadcrumb = 'Administración · Reporte del día ' . date('d/m/Y');

        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/reportes.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }
}
