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
        $result = $this->db->query("
            SELECT
                p.id, p.estatus, p.cuota, p.saldo_actual, p.frecuencia,
                c.nombre AS cliente_nombre, c.celular, c.direccion,
                MIN(pg.fecha_programada) AS proximo_pago,
                DATEDIFF(CURDATE(), MIN(pg.fecha_programada)) AS dias_atraso
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            LEFT JOIN pagos pg ON pg.prestamo_id = p.id AND pg.estatus IN ('Pendiente','Atrasado')
            WHERE p.estatus IN ('Activo','Atrasado')
            GROUP BY p.id
            ORDER BY p.estatus DESC, proximo_pago ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
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

    // Pasar a 'Retirado' los préstamos Pendiente con más de 7 días sin desembolsar
    public function expirePending(int $dias = 7): int {
        $result = $this->db->query("
            UPDATE prestamos
            SET estatus = 'Retirado'
            WHERE estatus = 'Pendiente'
              AND fecha_entrega IS NULL
              AND DATEDIFF(CURDATE(), DATE(created_at)) > {$dias}
        ");
        return (int)$this->db->affected_rows;
    }

    // ─── Interés diario ──────────────────────────────────────────────

    // Ejecutado por el cron a medianoche: acumula interés diario en cada préstamo activo
    public function accrueInterest(): int {
        $today = date('Y-m-d');
        $result = $this->db->query("
            SELECT id, saldo_actual, tasa_diaria, interes_acumulado,
                   fecha_ultimo_interes, fecha_inicio
            FROM prestamos
            WHERE estatus IN ('Activo','Atrasado')
              AND (fecha_ultimo_interes IS NULL OR fecha_ultimo_interes < '$today')
        ");
        $loans = $result->fetch_all(MYSQLI_ASSOC);
        $count = 0;

        foreach ($loans as $loan) {
            $base = $loan['fecha_ultimo_interes'] ?? $loan['fecha_inicio'] ?? $today;
            if ($base >= $today) continue;

            $dias          = (int)(new DateTime($today))->diff(new DateTime($base))->days;
            if ($dias < 1) continue;

            $interesDiario = (float)$loan['saldo_actual'] * ((float)$loan['tasa_diaria'] / 100);
            $nuevos        = $interesDiario * $dias;

            $stmt = $this->db->prepare("
                UPDATE prestamos
                SET interes_acumulado   = interes_acumulado + ?,
                    fecha_ultimo_interes = ?
                WHERE id = ?
            ");
            $stmt->bind_param("dsi", $nuevos, $today, $loan['id']);
            $stmt->execute();
            $stmt->close();
            $count++;
        }
        return $count;
    }

    // Información de interés en tiempo real para la vista de detalle
    public function getInterestInfo(int $id): array {
        $stmt = $this->db->prepare("
            SELECT saldo_actual, tasa_diaria, interes_acumulado,
                   fecha_ultimo_interes, fecha_inicio
            FROM prestamos WHERE id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return [];

        $td   = (float)$row['tasa_diaria'] / 100;
        $base = $row['fecha_ultimo_interes'] ?? $row['fecha_inicio'];
        $hoy  = date('Y-m-d');

        // Interés no acumulado aún (días desde último cron hasta hoy)
        $diasSinAcumular  = ($base && $base < $hoy)
            ? (int)(new DateTime($hoy))->diff(new DateTime($base))->days
            : 0;

        $interesDiario    = round((float)$row['saldo_actual'] * $td, 2);
        $interesPendiente = round($interesDiario * $diasSinAcumular, 2);
        $interesTotal     = round((float)$row['interes_acumulado'] + $interesPendiente, 2);
        $totalAdeudado    = round((float)$row['saldo_actual'] + $interesTotal, 2);

        return [
            'principal'          => (float)$row['saldo_actual'],
            'tasa_diaria'        => (float)$row['tasa_diaria'],
            'interes_diario'     => $interesDiario,
            'interes_acumulado'  => $interesTotal,
            'total_adeudado'     => $totalAdeudado,
            'dias_sin_acumular'  => $diasSinAcumular,
            'fecha_ultimo_interes' => $row['fecha_ultimo_interes'],
        ];
    }

    // Actualizar condiciones del préstamo (tasa, cuota, fecha fin)
    public function updateTerms(int $id, float $tasa_diaria, float $cuota, string $fecha_fin): void {
        $stmt = $this->db->prepare(
            "UPDATE prestamos SET tasa_diaria = ?, cuota = ?, fecha_fin = ? WHERE id = ?"
        );
        $stmt->bind_param("ddsi", $tasa_diaria, $cuota, $fecha_fin, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function updateMeta(int $id, array $data): void {
        $cobrador_id       = $data['cobrador_id']      ?: null;
        $promotor_id       = $data['promotor_id']      ?: null;
        $estatus           = $data['estatus'];
        $saldo_actual      = (float)$data['saldo_actual'];
        $interes_acumulado = (float)$data['interes_acumulado'];

        $stmt = $this->db->prepare("
            UPDATE prestamos
            SET cobrador_id = ?, promotor_id = ?, estatus = ?,
                saldo_actual = ?, interes_acumulado = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iisddi",
            $cobrador_id, $promotor_id, $estatus,
            $saldo_actual, $interes_acumulado, $id);
        $stmt->execute();
        $stmt->close();
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
