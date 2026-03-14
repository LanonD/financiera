<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo view 1</title>
</head>
<body>
    <ul>
        <li>
            Dar de alta
        </li>
    </ul>
    <label for="">Préstamo ID</label> <input type="text" id="search_id" name="search_id">
    <br>
    <label for="estatus">Estatus: </label>
    <label for="activo">Activo</label>
    <input type="checkbox" name="activo" id="activo">
    <label for="finalizado">Finalizado</label>
    <input type="checkbox" name="finalizado" id="finalizado">

    <label for="capacidad">Capacidad máxima de préstamo: </label>
    <span id="capacidad">$50000</span>
    <label for="ocupado">Monto ocupado: </label>
    <span id="ocupado">$26000</span>
    <label for="libre">Monto libre: </label>
    <span id="libre">$24000</span>

    <table>
        <thead>
            <th>ID</th>
            <th>Nombre</th>
            <th>Monto</th>
            <th>Plazo</th>
            <th>Pago</th>
            <th>Esquema</th>
            <th>Interés</th>
            <th>Fecha de contrato</th>
            <th>Fecha de inicio</th>
            <th>Estado</th>
        </thead>
        <tbody>
            
        </tbody>
    </table>


    <div id="modalRegistro" class="modal">

<div class="modal-content">

<span class="close">&times;</span>

<h2>Registrar Cliente</h2>

<form method="POST" action="php/registro_clientes.php" enctype="multipart/form-data">

<label>Nombre</label>
<input type="text" name="nombre" required>

<label>No. Celular</label>
<input type="text" name="celular" required>

<label>No. Fijo</label>
<input type="text" name="fijo" required>

<label>Dirección</label>
<input type="text" name="direccion" required>


<label>CURP</label>
<input type="text" name="curp" required>

<label>Ocupación</label>
<select name="ocupacion" required>
<option value="Empleado">Empleado</option>
<option value="Negocio propio">Negocio propio</option>
</select>


<label>Contactos de emergencia</label>
<label>Nombre</label>
<input type="text" name="contacto_nombre" required>
<label>Teléfono</label>
<input type="text" name="contacto_telefono" required>
<label>Dirección</label>
<input type="text" name="contacto_direccion" required>

<label>Nombre</label>
<input type="text" name="contacto_nombre2" required>
<label>Teléfono</label>
<input type="text" name="contacto_telefono2" required>
<label>Dirección</label>
<input type="text" name="contacto_direccion2" required>

<hr>

<label>INE</label>
<input type="file" name="ine" required>

<label>Pagaré</label>
<input type="file" name="pagare" required>

<label>Contrato</label>
<input type="file" name="contrato" required>

<label>Comprobante de domicilio</label>
<input type="file" name="comprobante" required>

<label>Ubicación exacta</label>

<div id="map"></div>

<input type="hidden" name="latitud" id="latitud">
<input type="hidden" name="longitud" id="longitud">

<button type="submit">Registrar</button>

</form>

</div>
</div>
<script>

const modal = document.getElementById("modalRegistro");
const btn = document.getElementById("openModal");
const close = document.querySelector(".close");

btn.onclick = function(){
    modal.style.display = "flex";
}

close.onclick = function(){
    modal.style.display = "none";
}

window.onclick = function(e){
    if(e.target == modal){
        modal.style.display = "none";
    }
}

let map;
let marker;

function initMap(){

    const defaultLocation = { lat:19.4326, lng:-99.1332 }; // CDMX

    map = new google.maps.Map(document.getElementById("map"),{
        zoom:14,
        center:defaultLocation
    });

    map.addListener("click",function(e){

        const lat = e.latLng.lat();
        const lng = e.latLng.lng();

        document.getElementById("latitud").value = lat;
        document.getElementById("longitud").value = lng;

        if(marker){
            marker.setMap(null);
        }

        marker = new google.maps.Marker({
            position:e.latLng,
            map:map
        });

    });

}

window.onload = initMap;

</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfb3MRYco1aN4yaJyXmK8jperHTMJl07E"></script>
</body>
</html>