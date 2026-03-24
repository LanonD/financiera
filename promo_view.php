<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo view 1</title>
</head>
<body>
    <button id="openModal">Dar de alta</button>
    <button id="openModalPrestamo">Nuevo préstamo</button>
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



<div id="modalPrestamo" class="modal">

<<div class="modal-content">
    
    <form id="formPrestamo">

      <div class="modal-header">
        <h3>Nuevo préstamo</h3>
        <span class="closePres">&times;</span>
      </div>

      <div class="modal-body">

        <!-- Cliente -->
        <label>Cliente</label>
        <input type="text" id="buscarCliente" placeholder="Escribe nombre...">
        <input type="hidden" name="cliente_id" id="cliente_id">
        <div id="resultados"></div>

        <div class="row">
          <div class="col">
            <label>Monto</label>
            <input type="number" name="monto" required>
          </div>

          <div class="col">
            <label>Pagos</label>
            <input type="number" name="pagos" required>
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label>Interés (%)</label>
            <input type="number" name="interes">
          </div>

          <div class="col">
            <label>Plazo</label>
            <select name="plazo">
              <option>Diario</option>
              <option>Semanal</option>
              <option>Mensual</option>
            </select>
          </div>
        </div>

        <label>Monto por pago</label>
        <input type="text" id="monto_pago" readonly>

        <div class="row">
          <div class="col">
            <label>Fecha préstamo</label>
            <input type="date" name="fecha_prestamo">
          </div>

          <div class="col">
            <label>Inicio cobro</label>
            <input type="date" name="fecha_inicio">
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
      </div>

    </form>

  </div>
</div>


<script>

    //calcular interés y pagos

    // inputs
const monto = document.querySelector("input[name='monto']");
const interes = document.querySelector("input[name='interes']");
const pagos = document.querySelector("input[name='pagos']");
const montoPago = document.getElementById("monto_pago");

// función cálculo
function calcularPago() {
    let m = parseFloat(monto.value) || 0;
    let i = parseFloat(interes.value) || 0;
    let p = parseInt(pagos.value) || 0;

    if (m > 0 && p > 0) {
        let total = m + (m * (i / 100));
        let pago = total / p;

        montoPago.value = pago.toFixed(2);
    } else {
        montoPago.value = "";
    }
}

// eventos en tiempo real
monto.addEventListener("input", calcularPago);
interes.addEventListener("input", calcularPago);
pagos.addEventListener("input", calcularPago);

// modal registro cliente

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


//modal nuevo préstamo

const modalPrestamo = document.getElementById("modalPrestamo");
const btnPres = document.getElementById("openModalPrestamo");
const closePres = document.querySelector(".close");

btnPres.onclick = function(){
    modalPrestamo.style.display = "flex";
}

closePres.onclick = function(){
    modalPrestamo.style.display = "none";
}

window.onclick = function(e){
    if(e.target == modalPrestamo){
        modalPrestamo.style.display = "none";
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


// buscar clientes
document.getElementById("buscarCliente").addEventListener("keyup", function() {
    let query = this.value;

    if (query.length < 2) {
        document.getElementById("resultados").innerHTML = "";
        return;
    }

    fetch("/php/buscar_cliente.php?q=" + query)
    .then(res => res.text())
    .then(data => {
        document.getElementById("resultados").innerHTML = data;
    });
});

// seleccionar cliente
function seleccionarCliente(id, nombre) {
    document.getElementById("cliente_id").value = id;
    document.getElementById("buscarCliente").value = nombre;
    document.getElementById("resultados").innerHTML = "";
}

// guardar préstamo
document.getElementById("formPrestamo").addEventListener("submit", function(e){
    e.preventDefault();

    let formData = new FormData(this);

    fetch("/php/guardar_prestamo.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        alert(data);
        location.reload();
    });
});
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfb3MRYco1aN4yaJyXmK8jperHTMJl07E"></script>
</body>
</html>