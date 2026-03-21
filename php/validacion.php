<?php

session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user = $_POST['user'];
    $pwd = $_POST['pwd'];

    $stmt = $conn->prepare("SELECT id, usuario, puesto, password FROM usuarios_f WHERE usuario = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $fila = $resultado->fetch_assoc();

        if (password_verify($pwd, $fila['password'])) {

            $_SESSION['usuario'] = $fila['usuario'];
            $_SESSION['id'] = $fila['id'];
            $_SESSION['puesto'] = $fila['puesto'];

            if ($fila['puesto'] === 'admin') {
                $_SESSION['admin'] = true;
                header("Location: ../admin_view.php");
            } 
            if ($fila['puesto'] === 'collector') {
                $_SESSION['admin'] = true;
                header("Location: ../collector_view.php");
            }
            if ($fila['puesto'] === 'promo') {
                $_SESSION['admin'] = true;
                header("Location: ../promo_view.php");
            }

            exit();

        } else {

            header("Location: ../login.php?error=password"); exit();
        }

    } else {

        header("Location: ../login.php?error=user"); exit();
    }

    $stmt->close();
}

$conn->close();

?>