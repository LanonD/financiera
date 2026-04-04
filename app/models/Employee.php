<?php
class Employee {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function findByUserId(int $usuario_id): ?array {
        $stmt = $this->db->prepare("
            SELECT e.* FROM empleados e
            JOIN usuarios_f u ON e.usuario_id = u.id
            WHERE u.id = ? AND e.activo = 1 LIMIT 1
        ");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM v_empleados ORDER BY puesto, nombre")->fetch_all(MYSQLI_ASSOC);
    }

    public function getByType(string $puesto): array {
        $stmt = $this->db->prepare("SELECT * FROM v_empleados WHERE puesto = ? ORDER BY nombre");
        $stmt->bind_param("s", $puesto);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function create(array $data, int $usuario_id): int {
        $stmt = $this->db->prepare("
            INSERT INTO empleados
            (usuario_id, nombre, celular, fijo, direccion, puesto, rango, capacidad_maxima,
             latitud, longitud,
             contacto_nombre, contacto_telefono, contacto_direccion,
             contacto_nombre2, contacto_telefono2, contacto_direccion2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssssddsssssss",
            $usuario_id,
            $data['nombre'], $data['celular'], $data['fijo'], $data['direccion'],
            $data['puesto'], $data['rango'], $data['capacidad'],
            $data['latitud'], $data['longitud'],
            $data['contacto_nombre'],   $data['contacto_telefono'],   $data['contacto_direccion'],
            $data['contacto_nombre2'],  $data['contacto_telefono2'],  $data['contacto_direccion2']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
}
