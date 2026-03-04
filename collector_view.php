<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector view 1</title>
</head>
<body>
    <ul>
        <li>
            Generar documento de cobro
        </li>
    </ul>
    <label for="">Préstamo ID</label> <input type="text" id="search_id" name="search_id">
    <br>
    <label for="estatus">Estatus: </label>
    <label for="activo">Activo</label>
    <input type="checkbox" name="activo" id="activo">
    <label for="pendiente">Pendiente</label>
    <input type="checkbox" name="pendiente" id="pendiente">
    <label for="atrasado">Atrasado</label>
    <input type="checkbox" name="atrasado" id="atrasado">

    <label for="rango">Rango actual: </label>
    <span id="rango">Plata</span>
    <label for="cobro">Monto máximo de cobro: </label>
    <span id="cobro">$20000</span>
 

    <table>
        <thead>
            <th>ID</th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Celular</th>
            <th>PLazo</th>
            <th>Pago</th>
            <th>Saldo actual</th>
            <th>Fecha de cobro</th>
            <th>Estatus</th>
            <th>Chack</th>
        </thead>
        <tbody>
            
        </tbody>
    </table>
</body>
</html>