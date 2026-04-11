<?php
class Client {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAll(): array {
        return $this->db->query("
            SELECT c.*, e.nombre AS promotor_nombre,
                   (SELECT COUNT(*) FROM prestamos p WHERE p.cliente_id = c.id AND p.estatus IN ('Activo', 'Atrasado', 'Pendiente')) AS prestamos_activos
            FROM clientes_f c
            LEFT JOIN empleados e ON c.promotor_id = e.id
            WHERE c.activo = 1 ORDER BY c.nombre
        ")->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nombre AS promotor_nombre
            FROM clientes_f c
            LEFT JOIN empleados e ON c.promotor_id = e.id
            WHERE c.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // Todos los préstamos del cliente con su historial de pagos.
    // Si $cobrador_id > 0 solo devuelve los préstamos asignados a ese cobrador.
    public function getLoansWithPayments(int $cliente_id, int $cobrador_id = 0): array {
        $cobWhere = $cobrador_id > 0 ? "AND p.cobrador_id = $cobrador_id" : "";
        // Loans
        $stmt = $this->db->prepare("
            SELECT p.*, e.nombre AS promotor_nombre
            FROM prestamos p
            LEFT JOIN empleados e ON p.promotor_id = e.id
            WHERE p.cliente_id = ? $cobWhere
            ORDER BY p.id DESC
        ");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($loans)) return [];

        // Payments for all loans in one query
        $ids  = implode(',', array_column($loans, 'id'));
        $rows = $this->db->query("
            SELECT *,
                CASE
                    WHEN fecha_pago IS NOT NULL
                    THEN DATEDIFF(fecha_pago, fecha_programada)
                    ELSE NULL
                END AS dias_diff
            FROM pagos
            WHERE prestamo_id IN ($ids)
            ORDER BY prestamo_id ASC, numero_pago ASC
        ")->fetch_all(MYSQLI_ASSOC);

        // Group payments by prestamo_id
        $byLoan = [];
        foreach ($rows as $r) { $byLoan[$r['prestamo_id']][] = $r; }

        foreach ($loans as &$loan) {
            $loan['pagos'] = $byLoan[$loan['id']] ?? [];
        }
        unset($loan);

        return $loans;
    }

    public function getByPromotor(int $promotor_id): array {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nombre AS promotor_nombre,
                   (SELECT COUNT(*) FROM prestamos p WHERE p.cliente_id = c.id AND p.estatus IN ('Activo', 'Atrasado', 'Pendiente')) AS prestamos_activos
            FROM clientes_f c
            LEFT JOIN empleados e ON c.promotor_id = e.id
            WHERE c.promotor_id = ? AND c.activo = 1 ORDER BY c.nombre
        ");
        $stmt->bind_param("i", $promotor_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare("
            UPDATE clientes_f
            SET nombre = ?, celular = ?, email = ?, fijo = ?,
                direccion = ?, curp = ?, ocupacion = ?, promotor_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssssssii",
            $data['nombre'], $data['celular'], $data['email'], $data['fijo'],
            $data['direccion'], $data['curp'], $data['ocupacion'],
            $data['promotor_id'], $id);
        $stmt->execute();
        $stmt->close();
    }

    public function create(array $data, int $promotor_id): int {
        $stmt = $this->db->prepare("
            INSERT INTO clientes_f
            (promotor_id, nombre, celular, email, fijo, direccion, curp, ocupacion,
             ine, pagare, contrato, comprobante, foto_vivienda,
             latitud, longitud,
             contacto_nombre, contacto_telefono, contacto_direccion,
             contacto_nombre2, contacto_telefono2, contacto_direccion2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssssssssssddssssss",
            $promotor_id,
            $data['nombre'], $data['celular'], $data['email'], $data['fijo'],
            $data['direccion'], $data['curp'], $data['ocupacion'],
            $data['ine'], $data['pagare'], $data['contrato'],
            $data['comprobante'], $data['foto_vivienda'],
            $data['latitud'], $data['longitud'],
            $data['contacto_nombre'],   $data['contacto_telefono'],   $data['contacto_direccion'],
            $data['contacto_nombre2'],  $data['contacto_telefono2'],  $data['contacto_direccion2']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function search(array $filters): array {
        $where  = ["c.activo = 1"];
        $params = [];
        $types  = "";

        if (!empty($filters['nombre'])) {
            $where[]  = "c.nombre LIKE ?";
            $params[] = '%' . $filters['nombre'] . '%';
            $types   .= "s";
        }
        if (!empty($filters['celular'])) {
            $where[]  = "c.celular LIKE ?";
            $params[] = '%' . $filters['celular'] . '%';
            $types   .= "s";
        }
        if (!empty($filters['curp'])) {
            $where[]  = "c.curp = ?";
            $params[] = $filters['curp'];
            $types   .= "s";
        }

        $sql  = "SELECT c.*, e.nombre AS promotor_nombre FROM clientes_f c JOIN empleados e ON c.promotor_id = e.id";
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY c.nombre LIMIT 50";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
