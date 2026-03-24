<?php

require 'conexion.php';

$cliente_id = $_POST['cliente_id'];
$monto = $_POST['monto'];
$pagos = $_POST['pagos'];
$interes = $_POST['interes'];
$plazo = $_POST['plazo'];
$fecha_prestamo = $_POST['fecha_prestamo'];
$fecha_inicio = $_POST['fecha_inicio'];

$sql = "INSERT INTO prestamos 
(cliente_id, monto, pagos, interes, plazo, fecha_prestamo, fecha_inicio)
VALUES 
('$cliente_id', '$monto', '$pagos', '$interes', '$plazo', '$fecha_prestamo', '$fecha_inicio')";

if ($conn->query($sql)) {
    echo "Préstamo guardado correctamente";
} else {
    echo "Error: " . $conn->error;
}
?>