<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Search</title>
</head>
<body>
    <ul>
        <li>Vista general</li>
        <li>Funciones</li>
        <li>Búsqueda</li>
        <li>Gráficas</li>
    </ul>
    <h3>Búsqueda personalizada</h3>
    <h4>Clientes</h4>
    <label for="id_prestamo">Préstamo ID: </label> <input type="text" name="id_prestamo" id="id_prestamo">
    <br>
    <label for="c_name">Nombre: </label> <input type="text" name="c_name" id="c_name">
    <br>
    <label for="c_phone">Celular: </label> <input type="text" name="c_phone" id="c_phone">
    <br>
    <label for="c_id">Cliente ID: </label> <input type="text" name="c_id" id="c_id">
    <br>
    <label for="c_address">Dirección: </label> <input type="text" name="c_address" id="c_address">
    <br>

    <h4>Empleados</h4>
    <label for="e_name">Nombre: </label> <input type="text" name="e_name" id="e_name">
    <br>
    <label for="e_phone">Celular: </label> <input type="text" name="e_phone" id="e_phone">
    <br>
    <label for="e_id">Empleado ID: </label> <input type="text" name="e_id" id="e_id">
    <br>
    <label for="e_address">Dirección: </label> <input type="text" name="e_address" id="e_address">
    <br>

    <h2>Resultado de búsqueda</h2>
    <div>
        <label for="">Nombre: </label> <span></span><br>
        <label for="">Préstamo ID: </label> <span></span><br>
        <label for="">Cliente ID: </label> <span></span><br>
        <label for="">Estatus: </label> <span></span><br>
        <label for="">Atraso: </label> <span></span><br>
        <label for="">Balance total: </label> <span></span><br>
        <label for="">Tasa porcentual anual: </label> <span></span><br>
        <label for="">Principal: </label> <span></span><br>
        <label for="">Interés diario: </label> <span></span><br>
        <label for="">Total pagado: </label> <span></span><br>
        <button>Contrato</button><button>Historial</button><br>
    </div>
    <div>
        <label for="">Nombre: </label> <span></span><br>
        <label for="">Empleado ID: </label> <span></span><br>
        <label for="">Rango: </label> <span></span><br>
        <label for="">Fecha de contratación: </label> <span></span><br>
        <label for="">Clientes: </label> <span></span><br>
        <button>Contrato</button>
    </div>
    
</body>
</html>