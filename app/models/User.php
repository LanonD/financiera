<?php
class User {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, usuario, puesto, password FROM usuarios_f WHERE usuario = ? AND activo = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, usuario, puesto FROM usuarios_f WHERE id = ? AND activo = 1 LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function create(string $usuario, string $password, string $puesto): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios_f (usuario, password, puesto) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $usuario, $hash, $puesto);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
}
