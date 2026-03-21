<?php

require 'conexion.php';

$nombre = $_POST['nombre'];
$celular = $_POST['celular'];
$fijo = $_POST['fijo'];
$direccion = $_POST['direccion'];
$ocupacion = $_POST['ocupacion'];
$curp = $_POST['curp'];


$contacto_nombre = $_POST['contacto_nombre'];
$contacto_telefono = $_POST['contacto_telefono'];
$contacto_direccion = $_POST['contacto_direccion'];
$contacto_nombre2 = $_POST['contacto_nombre2'];
$contacto_telefono2 = $_POST['contacto_telefono2'];
$contacto_direccion2 = $_POST['contacto_direccion2'];



$latitud = $_POST['latitud'];
$longitud = $_POST['longitud'];


/* CREAR CARPETA DEL CLIENTE */

$folder = "../uploads/clientes/" . $curp;

if(!file_exists($folder)){
    mkdir($folder, 0777, true);
}


/* FUNCION PARA SUBIR ARCHIVOS */

function subirArchivo($file,$folder){

    $nombreArchivo = basename($file["name"]);

    $ruta = $folder . "/" . $nombreArchivo;

    move_uploaded_file($file["tmp_name"], $ruta);

    return $ruta;
}


/* SUBIR DOCUMENTOS */

$rutaINE = subirArchivo($_FILES['ine'],$folder);

$rutaPagare = subirArchivo($_FILES['pagare'],$folder);

$rutaContrato = subirArchivo($_FILES['contrato'],$folder);

$rutaComprobante = subirArchivo($_FILES['comprobante'],$folder);


/* INSERTAR EN BASE DE DATOS */

$stmt = $conn->prepare("INSERT INTO clientes_f
(nombre, celular, fijo, direccion, ocupacion, curp, ine, pagare, contrato, comprobante, latitud, longitud, contacto_nombre, contacto_telefono, contacto_direccion, contacto_nombre2, contacto_telefono2, contacto_direccion2) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param("ssssssssssssssssss",
$nombre,
$celular,
$fijo,
$direccion,
$ocupacion,
$curp,
$rutaINE,
$rutaPagare,
$rutaContrato,
$rutaComprobante,
$latitud,
$longitud,
$contacto_nombre,
$contacto_telefono,
$contacto_direccion,
$contacto_nombre2,
$contacto_telefono2,
$contacto_direccion2
);


$stmt->execute();


header("Location: ../promo_view.php");

?>