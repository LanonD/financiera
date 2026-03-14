<?php

$host = "u172823920_admin";
$user = "root";
$password = "VazGra66?";
$db = "u172823920_lavanderia";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

?>