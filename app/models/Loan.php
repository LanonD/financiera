<?php
class Loan {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    // Todos los préstamos con datos de cliente y empleados
    public function getAll(): array {
        $result = $this->db->query("
            SELECT
                v.*,
                (SELECT MIN(fecha_programada) FROM pagos pg WHERE pg.prestamo_id = v.id AND pg.estatus IN ('Pendiente','Atrasado')) AS proximo_pago
            FROM v_prestamos v
            ORDER BY v.id DESC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Préstamos con filtros opcionales de servidor.
    // $promotor_id > 0 limita a ese promotor (rol promo).
    // Los demás parámetros son opcionales; vacíos/ceros = sin filtro.
    public function getFiltered(
        int    $promotor_id = 0,
        string $frecuencia  = '',
        float  $monto_min   = 0,
        float  $monto_max   = 0,
        string $desde       = '',
        string $hasta       = ''
    ): array {
        // Sanitize dates
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : '';
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : '';

        $where = [];
        if ($promotor_id > 0) $where[] = "p.promotor_id = " . (int)$promotor_id;
        if ($frecuencia !== '') {
            $f       = $this->db->real_escape_string($frecuencia);
            $where[] = "p.frecuencia = '$f'";
        }
        if ($monto_min > 0) $where[] = "p.monto >= " . (float)$monto_min;
        if ($monto_max > 0) $where[] = "p.monto <= " . (float)$monto_max;

        $whereStr = $where ? "WHERE " . implode(" AND ", $where) : "";

        // proximo_pago es un agregado (MIN), se filtra con HAVING
        $having = [];
        if ($desde !== '') $having[] = "(proximo_pago IS NOT NULL AND proximo_pago >= '$desde')";
        if ($hasta !== '') $having[] = "(proximo_pago IS NOT NULL AND proximo_pago <= '$hasta')";
        $havingStr = $having ? "HAVING " . implode(" AND ", $having) : "";

        $result = $this->db->query("
            SELECT
                p.id, p.estatus, p.monto, p.cuota, p.frecuencia,
                p.saldo_actual, p.interes_acumulado, p.tasa_diaria,
                p.num_pagos, p.fecha_inicio, p.fecha_fin,
                c.nombre AS cliente_nombre, c.id AS cliente_id,
                ep.nombre AS promotor_nombre,
                ec.nombre AS cobrador_nombre,
                MIN(pg.fecha_programada) AS proximo_pago
            FROM prestamos p
            JOIN clientes_f c      ON p.cliente_id  = c.id
            LEFT JOIN empleados ep ON p.promotor_id  = ep.id
            LEFT JOIN empleados ec ON p.cobrador_id  = ec.id
            LEFT JOIN pagos pg     ON pg.prestamo_id = p.id
                                  AND pg.estatus IN ('Pendiente','Atrasado')
            $whereStr
            GROUP BY p.id
            $havingStr
            ORDER BY p.id DESC
        ");
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

    // Préstamos de un cobrador específico.
    // $soloHoy = true → solo muestra préstamos con pago vencido o de hoy (uso cobrador).
    // $soloHoy = false → muestra todos los activos/atrasados (uso admin).
    public function getByCollector(int $cobrador_id, bool $soloHoy = false): array {
        $whereId = $cobrador_id > 0
            ? "AND p.cobrador_id = " . (int)$cobrador_id
            : "";
        // HAVING filtra tras el GROUP BY: solo pasan loans con proximo_pago <= hoy.
        // Al marcar una cuota como Pagada, el MIN salta a la siguiente fecha (futura)
        // y el préstamo desaparece de la lista del cobrador hasta ese día.
        $having = $soloHoy
            ? "HAVING proximo_pago IS NOT NULL AND proximo_pago <= CURDATE()"
            : "";
        $result = $this->db->query("
            SELECT
                p.id, p.estatus, p.cuota, p.saldo_actual, p.frecuencia,
                c.id   AS cliente_id,
                c.nombre AS cliente_nombre, c.celular, c.direccion,
                MIN(pg.fecha_programada) AS proximo_pago,
                DATEDIFF(CURDATE(), MIN(pg.fecha_programada)) AS dias_atraso
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            LEFT JOIN pagos pg ON pg.prestamo_id = p.id AND pg.estatus IN ('Pendiente','Atrasado')
            WHERE p.estatus IN ('Activo','Atrasado') $whereId
            GROUP BY p.id
            $having
            ORDER BY p.estatus DESC, proximo_pago ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Préstamos activos/atrasados con info de cobrador, para pantalla de asignación.
    // $desde / $hasta filtran por proximo_pago (HAVING, ya que es agregado).
    // $sinCobrador limita a préstamos sin cobrador asignado.
    // $busqueda filtra por nombre del cliente (LIKE).
    public function getActiveForAssignment(
        int    $promotor_id  = 0,
        string $desde        = '',
        string $hasta        = '',
        bool   $sinCobrador  = false,
        string $busqueda     = ''
    ): array {
        // Sanitize dates — accept only YYYY-MM-DD
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : '';
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : '';

        $where = "p.estatus IN ('Activo','Atrasado')";
        if ($promotor_id > 0) $where .= " AND p.promotor_id = " . (int)$promotor_id;
        if ($sinCobrador)     $where .= " AND p.cobrador_id IS NULL";
        if ($busqueda !== '') {
            $b      = $this->db->real_escape_string($busqueda);
            $where .= " AND c.nombre LIKE '%$b%'";
        }

        // HAVING filters on the GROUP BY aggregate proximo_pago
        $having = [];
        if ($desde !== '') $having[] = "proximo_pago >= '$desde'";
        if ($hasta !== '') $having[] = "proximo_pago <= '$hasta'";
        $havingStr = !empty($having) ? "HAVING " . implode(" AND ", $having) : "";

        $result = $this->db->query("
            SELECT
                p.id, p.estatus, p.cuota, p.saldo_actual, p.frecuencia,
                p.cobrador_id,
                c.id      AS cliente_id,
                c.nombre  AS cliente_nombre,
                c.celular AS cliente_celular,
                ec.nombre AS cobrador_nombre,
                ec.id     AS cobrador_emp_id,
                MIN(pg.fecha_programada) AS proximo_pago,
                DATEDIFF(CURDATE(), MIN(pg.fecha_programada)) AS dias_atraso,
                (SELECT COUNT(*) FROM pagos pg2
                 WHERE pg2.prestamo_id = p.id
                   AND DATE(pg2.fecha_pago) = CURDATE()
                   AND pg2.estatus IN ('Pagado','Parcial')) AS pagado_hoy,
                (SELECT pg3.estatus FROM pagos pg3
                 WHERE pg3.prestamo_id = p.id
                   AND DATE(pg3.fecha_pago) = CURDATE()
                   AND pg3.estatus IN ('Pagado','Parcial')
                 LIMIT 1) AS tipo_pago_hoy
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            LEFT JOIN empleados ec ON p.cobrador_id = ec.id
            LEFT JOIN pagos pg ON pg.prestamo_id = p.id AND pg.estatus IN ('Pendiente','Atrasado')
            WHERE $where
            GROUP BY p.id
            $havingStr
            ORDER BY p.estatus DESC, dias_atraso DESC, proximo_pago ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Asignar un cobrador a un préstamo
    public function assignCollector(int $prestamo_id, int $cobrador_id): void {
        $val  = $cobrador_id > 0 ? $cobrador_id : null;
        $stmt = $this->db->prepare("UPDATE prestamos SET cobrador_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $val, $prestamo_id);
        $stmt->execute();
        $stmt->close();
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

    // Pagos de un préstamo — incluye nombre del cobrador que registró cada pago
    public function getPayments(int $prestamo_id): array {
        $stmt = $this->db->prepare("
            SELECT pg.*, e.nombre AS cobrador_nombre
            FROM pagos pg
            LEFT JOIN empleados e ON pg.cobrador_id = e.id
            WHERE pg.prestamo_id = ?
            ORDER BY pg.numero_pago ASC
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

    public function getPendingDisbursementByPromotor(int $promotor_id): array {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nombre AS cliente_nombre, c.celular, c.direccion,
                   e.nombre AS promotor_nombre
            FROM prestamos p
            JOIN clientes_f c ON p.cliente_id = c.id
            JOIN empleados  e ON p.promotor_id = e.id
            WHERE p.estatus = 'Pendiente' AND p.fecha_entrega IS NULL
              AND p.promotor_id = ?
            ORDER BY p.created_at ASC
        ");
        $stmt->bind_param("i", $promotor_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
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

    // Ejecutado por el cron a medianoche: acumula interés diario en cada préstamo activo.
    // Reutiliza la misma lógica para interés regular (tasa_diaria %) e interés por mora
    // (interes_diario fijo). Ambos son aditivos y van al mismo campo interes_acumulado.
    public function accrueInterest(): int {
        $today = date('Y-m-d');
        // Procesa loans con interés regular activo O mora activa (o ambos)
        $result = $this->db->query("
            SELECT id, saldo_actual, tasa_diaria, interes_acumulado,
                   fecha_ultimo_interes, fecha_inicio,
                   interes_activo, interes_mora_activo, interes_diario
            FROM prestamos
            WHERE estatus IN ('Activo','Atrasado')
              AND (interes_activo = 1 OR interes_mora_activo = 1)
              AND (fecha_ultimo_interes IS NULL OR fecha_ultimo_interes < '$today')
        ");
        $loans = $result->fetch_all(MYSQLI_ASSOC);
        $count = 0;

        foreach ($loans as $loan) {
            $base = $loan['fecha_ultimo_interes'] ?? $loan['fecha_inicio'] ?? $today;
            if ($base >= $today) continue;

            $dias = (int)(new DateTime($today))->diff(new DateTime($base))->days;
            if ($dias < 1) continue;

            // Interés regular (porcentaje sobre saldo)
            $interesRegular = $loan['interes_activo']
                ? (float)$loan['saldo_actual'] * ((float)$loan['tasa_diaria'] / 100) * $dias
                : 0.0;

            // Interés por mora (monto fijo diario). Si interes_diario = 0, no suma nada
            // aunque el flag esté activo (funcionalidad activa sin cargo = 0).
            $interesMora = ($loan['interes_mora_activo'] && (float)$loan['interes_diario'] > 0)
                ? (float)$loan['interes_diario'] * $dias
                : 0.0;

            $nuevos = $interesRegular + $interesMora;
            if ($nuevos <= 0) continue; // mora activa pero interes_diario = 0 → omitir

            $stmt = $this->db->prepare("
                UPDATE prestamos
                SET interes_acumulado    = interes_acumulado + ?,
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
                   fecha_ultimo_interes, fecha_inicio,
                   interes_activo, interes_mora_activo, interes_diario
            FROM prestamos WHERE id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return [];

        $td  = (float)$row['tasa_diaria'] / 100;
        $base = $row['fecha_ultimo_interes'] ?? $row['fecha_inicio'];
        $hoy  = date('Y-m-d');

        $diasSinAcumular = ($base && $base < $hoy)
            ? (int)(new DateTime($hoy))->diff(new DateTime($base))->days
            : 0;

        // Interés regular pendiente (días sin cron × tasa_diaria)
        $interesRegularDiario  = round((float)$row['saldo_actual'] * $td, 2);
        $interesRegularPend    = $row['interes_activo']
            ? round($interesRegularDiario * $diasSinAcumular, 2)
            : 0.0;

        // Interés mora pendiente (días sin cron × monto fijo)
        $interesMoraDiario = (float)$row['interes_diario'];
        $interesMoraPend   = ($row['interes_mora_activo'] && $interesMoraDiario > 0)
            ? round($interesMoraDiario * $diasSinAcumular, 2)
            : 0.0;

        $interesDiarioTotal = $interesRegularDiario + ($row['interes_mora_activo'] ? $interesMoraDiario : 0);
        $interesTotal       = round((float)$row['interes_acumulado'] + $interesRegularPend + $interesMoraPend, 2);
        $totalAdeudado      = round((float)$row['saldo_actual'] + $interesTotal, 2);

        return [
            'principal'           => (float)$row['saldo_actual'],
            'tasa_diaria'         => (float)$row['tasa_diaria'],
            'interes_diario'      => $interesDiarioTotal,        // total diario (regular + mora)
            'interes_diario_mora' => $interesMoraDiario,         // solo el cargo fijo de mora
            'interes_acumulado'   => $interesTotal,
            'total_adeudado'      => $totalAdeudado,
            'dias_sin_acumular'   => $diasSinAcumular,
            'fecha_ultimo_interes'=> $row['fecha_ultimo_interes'],
            'interes_mora_activo' => (int)$row['interes_mora_activo'],
            'interes_activo'      => (int)$row['interes_activo'],
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
        $current           = $this->findById($id);
        $cobrador_id       = !empty($data['cobrador_id']) ? (int)$data['cobrador_id'] : null;
        $promotor_id       = !empty($data['promotor_id']) ? (int)$data['promotor_id'] : $current['promotor_id'];
        $estatus           = $data['estatus'];
        $saldo_actual      = (float)$data['saldo_actual'];
        $interes_acumulado = (float)$data['interes_acumulado'];
        $interes_diario    = max(0.0, (float)($data['interes_diario'] ?? 0));

        $stmt = $this->db->prepare("
            UPDATE prestamos
            SET cobrador_id = ?, promotor_id = ?, estatus = ?,
                saldo_actual = ?, interes_acumulado = ?, interes_diario = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iisdddi",
            $cobrador_id, $promotor_id, $estatus,
            $saldo_actual, $interes_acumulado, $interes_diario, $id);
        $stmt->execute();
        $stmt->close();
    }

    // Activar / desactivar interés por mora — devuelve el nuevo valor (0 o 1)
    public function toggleMoraInterest(int $id): int {
        $this->db->query("
            UPDATE prestamos SET interes_mora_activo = 1 - interes_mora_activo WHERE id = $id
        ");
        $row = $this->db->query("SELECT interes_mora_activo FROM prestamos WHERE id = $id LIMIT 1")->fetch_assoc();
        return (int)($row['interes_mora_activo'] ?? 0);
    }

    // Pausar / reanudar interés diario regular de un préstamo — devuelve el nuevo valor
    public function toggleInterest(int $id): int {
        $this->db->query("
            UPDATE prestamos SET interes_activo = 1 - interes_activo WHERE id = $id
        ");
        $row = $this->db->query("SELECT interes_activo FROM prestamos WHERE id = $id LIMIT 1")->fetch_assoc();
        return (int)($row['interes_activo'] ?? 1);
    }

    // Crear nuevo préstamo.
    // $data puede incluir:
    //   'interes_activo'    (default 1) — 0 para préstamos de pago fijo (Calc2)
    //   'interes_acumulado' (default 0) — para préstamos Calc2: ganancia pre-cargada
    public function create(array $data): int {
        $interes_activo    = (int)  ($data['interes_activo']    ?? 1);
        $interes_acumulado = (float)($data['interes_acumulado'] ?? 0);
        $stmt = $this->db->prepare("
            INSERT INTO prestamos
            (cliente_id, promotor_id, cobrador_id, monto, tasa_diaria, num_pagos, frecuencia,
             cuota, saldo_actual, interes_acumulado, fecha_inicio, fecha_fin, estatus, interes_activo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', ?)
        ");
        $stmt->bind_param(
            "iiiddisdddssi",
            $data['cliente_id'], $data['promotor_id'], $data['cobrador_id'],
            $data['monto'], $data['tasa_diaria'], $data['num_pagos'],
            $data['frecuencia'], $data['cuota'], $data['saldo_actual'],
            $interes_acumulado,
            $data['fecha_inicio'], $data['fecha_fin'],
            $interes_activo
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
}
