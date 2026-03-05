<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Admin Search</title>
    <style>
        .resultado{
    margin-top: 15px;
}

.bloque{
    margin-bottom: 25px;
}

.bloque p{
    margin: 6px 0;
}

.botones{
    margin-top: 10px;
}

.botones button{
    margin-right: 8px;
}
    </style>
    
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

<div class="resultado">

    <div class="bloque">
        <p><label>Nombre:</label> <span></span></p>
        <p><label>Préstamo ID:</label> <span></span></p>
        <p><label>Cliente ID:</label> <span></span></p>
        <p><label>Estatus:</label> <span></span></p>
        <p><label>Atraso:</label> <span></span></p>
        <p><label>Balance total:</label> <span></span></p>
        <p><label>Tasa porcentual anual:</label> <span></span></p>
        <p><label>Principal:</label> <span></span></p>
        <p><label>Interés diario:</label> <span></span></p>
        <p><label>Total pagado:</label> <span></span></p>

        <div class="botones">
            <button>Contrato</button>
            <button>Historial</button>
        </div>
    </div>

    <div class="bloque">
        <p><label>Nombre:</label> <span></span></p>
        <p><label>Empleado ID:</label> <span></span></p>
        <p><label>Rango:</label> <span></span></p>
        <p><label>Fecha de contratación:</label> <span></span></p>
        <p><label>Clientes:</label> <span></span></p>

        <div class="botones">
            <button>Contrato</button>
        </div>
    </div>

</div>
    
</body>
</html>