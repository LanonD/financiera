<?php
class Client {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM clientes_f WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM clientes_f WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getByPromotor(int $promotor_id): array {
        $stmt = $this->db->prepare("SELECT * FROM clientes_f WHERE promotor_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->bind_param("i", $promotor_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function create(array $data, int $promotor_id): int {
        $stmt = $this->db->prepare("
            INSERT INTO clientes_f
            (promotor_id, nombre, celular, fijo, direccion, curp, ocupacion,
             ine, pagare, contrato, comprobante, foto_vivienda,
             latitud, longitud,
             contacto_nombre, contacto_telefono, contacto_direccion,
             contacto_nombre2, contacto_telefono2, contacto_direccion2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssssssssssddsssssss",
            $promotor_id,
            $data['nombre'], $data['celular'], $data['fijo'],
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
