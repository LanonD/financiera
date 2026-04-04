<?php
class Loan {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    // Todos los préstamos con datos de cliente y empleados
    public function getAll(): array {
        $result = $this->db->query("SELECT * FROM v_prestamos ORDER BY id DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Un préstamo por ID
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM v_prestamos WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // Préstamos de un cobrador específico
    public function getByCollector(int $cobrador_id): array {
        $stmt = $this->db->prepare("
            SELECT
                p.id, p.estatus, p.cuota, p.saldo_actual, p.frecuencia,
                c.nombre AS cliente_nombre, c.celular, c.direccion,
                MIN(pg.fecha_programada) AS proximo_pago,
                DATEDIFF(CURDATE(), MIN(pg.fecha_programada)) AS dias_atraso
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            LEFT JOIN pagos pg ON pg.prestamo_id = p.id AND pg.estatus IN ('Pendiente','Atrasado')
            WHERE p.cobrador_id = ? AND p.estatus IN ('Activo','Atrasado','Pendiente')
            GROUP BY p.id
            ORDER BY p.estatus DESC, proximo_pago ASC
        ");
        $stmt->bind_param("i", $cobrador_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // Préstamos de un promotor específico
    public function getByPromotor(int $promotor_id): array {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nombre AS cliente_nombre
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            WHERE p.promotor_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $promotor_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // Pagos de un préstamo
    public function getPayments(int $prestamo_id): array {
        $stmt = $this->db->prepare("
            SELECT * FROM pagos WHERE prestamo_id = ? ORDER BY numero_pago ASC
        ");
        $stmt->bind_param("i", $prestamo_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // Préstamos pendientes de desembolso
    public function getPendingDisbursement(): array {
        $result = $this->db->query("
            SELECT p.*, c.nombre AS cliente_nombre, c.celular, c.direccion,
                   e.nombre AS promotor_nombre
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            JOIN empleados  e ON p.promotor_id = e.id
            WHERE p.estatus = 'Pendiente' AND p.fecha_entrega IS NULL
            ORDER BY p.created_at ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // KPIs para el dashboard
    public function getKPIs(): array {
        $result = $this->db->query("
            SELECT
                COUNT(*)                                                      AS total,
                SUM(CASE WHEN estatus='Activo'    THEN 1 ELSE 0 END)         AS activos,
                SUM(CASE WHEN estatus='Pendiente' THEN 1 ELSE 0 END)         AS pendientes,
                SUM(CASE WHEN estatus='Atrasado'  THEN 1 ELSE 0 END)         AS atrasados,
                SUM(CASE WHEN estatus='Finalizado'THEN 1 ELSE 0 END)         AS finalizados,
                COALESCE(SUM(saldo_actual), 0)                               AS cartera_total,
                COALESCE(SUM(CASE WHEN estatus='Activo' THEN saldo_actual END), 0) AS cartera_activa
            FROM prestamos
        ");
        return $result->fetch_assoc();
    }

    // Crear nuevo préstamo
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO prestamos
            (cliente_id, promotor_id, cobrador_id, monto, tasa_diaria, num_pagos, frecuencia, cuota, saldo_actual, fecha_inicio, fecha_fin, estatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')
        ");
        $stmt->bind_param(
            "iiiddisddss",
            $data['cliente_id'], $data['promotor_id'], $data['cobrador_id'],
            $data['monto'], $data['tasa_diaria'], $data['num_pagos'],
            $data['frecuencia'], $data['cuota'], $data['saldo_actual'],
            $data['fecha_inicio'], $data['fecha_fin']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
}
