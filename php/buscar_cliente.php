<?php

require 'conexion.php';

$q = $_GET['q'];

$sql = "SELECT * FROM clientes WHERE nombre LIKE '%$q%' LIMIT 10";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo "<a href='#' class='list-group-item list-group-item-action'
            onclick=\"seleccionarCliente('{$row['id']}', '{$row['nombre']}')\">
            {$row['nombre']}
          </a>";
}
?>