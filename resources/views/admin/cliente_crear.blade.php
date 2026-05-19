@extends('layouts.app')

@section('title', 'Registrar cliente')

@push('styles')
<style>
.frm-grid-2col { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
.frm-field label { display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text3); margin-bottom:5px }
.frm-field input,
.frm-field select { width:100%; padding:9px 11px; background:#f9fafb; border:1.5px solid var(--border); border-radius:6px; font-family:var(--font); font-size:13px; outline:none; transition:border-color .15s }
.frm-field input:focus,
.frm-field select:focus { border-color:var(--accent); background:#fff }
.frm-field input.is-error,
.frm-field select.is-error { border-color:#ef4444 }
.frm-error { font-size:11px; color:#dc2626; margin-top:4px }
@media(max-width:600px){
    .frm-grid-2col{ grid-template-columns:1fr!important; }
    .frm-grid-2col [style*="grid-column"]{ grid-column:unset!important; }
    .frm-footer .btn{ flex:1; justify-content:center; }
}
</style>
@endpush

@section('content')

<a href="{{ route('clientes.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);margin-bottom:16px;text-decoration:none">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"/></svg>
    Volver a clientes
</a>

<div style="margin-bottom:20px">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Registrar cliente</h2>
    <p style="color:var(--text2);font-size:13px">Nuevo cliente del sistema</p>
</div>

{{-- Errores de validación --}}
@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;margin-bottom:16px;max-width:700px">
    <div style="font-size:13px;font-weight:600;color:#991b1b;margin-bottom:6px">Corrige los siguientes errores:</div>
    <ul style="margin:0;padding-left:18px;font-size:12px;color:#b91c1c">
        @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card" style="max-width:700px;padding:0;overflow:hidden">
<form method="POST" action="{{ route('clientes.store') }}" id="frmCliente">
    @csrf

    <div style="padding:20px">
        <div class="frm-grid-2col">

            {{-- Nombre (span completo) --}}
            <div style="grid-column:1/-1" class="frm-field">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                       class="{{ $errors->has('nombre') ? 'is-error' : '' }}">
                @error('nombre')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Celular --}}
            <div class="frm-field">
                <label>Celular</label>
                <input type="tel" name="celular" value="{{ old('celular') }}"
                       class="{{ $errors->has('celular') ? 'is-error' : '' }}">
                @error('celular')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div class="frm-field">
                <label>Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="{{ $errors->has('email') ? 'is-error' : '' }}">
                @error('email')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- CURP --}}
            <div class="frm-field">
                <label>CURP</label>
                <input type="text" name="curp" value="{{ old('curp') }}" maxlength="18"
                       style="text-transform:uppercase"
                       class="{{ $errors->has('curp') ? 'is-error' : '' }}">
                @error('curp')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Ocupación --}}
            <div class="frm-field">
                <label>Ocupación</label>
                <select name="ocupacion">
                    <option value="">— Seleccionar —</option>
                    @foreach(['Empleado','Negocio propio','Independiente','Otro'] as $op)
                    <option value="{{ $op }}" {{ old('ocupacion') === $op ? 'selected' : '' }}>{{ $op }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Dirección (span completo) --}}
            <div style="grid-column:1/-1" class="frm-field">
                <label>Dirección</label>
                <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}"
                       autocomplete="off"
                       class="{{ $errors->has('direccion') ? 'is-error' : '' }}">
                @error('direccion')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Mapa (span completo) --}}
            <div style="grid-column:1/-1">
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">
                    Ubicación en mapa <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text3)">(opcional)</span>
                </label>
                <div id="map" style="width:100%;height:280px;border-radius:8px;border:1px solid var(--border);overflow:hidden;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                    <span id="map-placeholder" style="font-size:12px;color:var(--text3)">Cargando mapa…</span>
                </div>
                <input type="hidden" name="latitud"  id="latitud"  value="{{ old('latitud') }}">
                <input type="hidden" name="longitud" id="longitud" value="{{ old('longitud') }}">
                <p style="font-size:12px;color:var(--text2);margin-top:6px">Da clic en el mapa o mueve el marcador para seleccionar la ubicación exacta.</p>
            </div>

            {{-- Promotor (solo admin) --}}
            @if(auth()->user()->puesto === 'admin')
            <div style="grid-column:1/-1" class="frm-field">
                <label>Promotor</label>
                <select name="promotor_id" class="{{ $errors->has('promotor_id') ? 'is-error' : '' }}">
                    <option value="">— Sin asignar —</option>
                    @foreach($promotores as $p)
                    <option value="{{ $p->id }}" {{ old('promotor_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                    @endforeach
                </select>
                @error('promotor_id')<p class="frm-error">{{ $message }}</p>@enderror
            </div>
            @endif

        </div>
    </div>

    <div class="frm-footer" style="padding:14px 20px;border-top:1px solid var(--border);background:#f9fafb;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap">
        <a href="{{ route('clientes.index') }}" class="btn" style="background:#f3f4f6;color:var(--text)">Cancelar</a>
        <button type="submit" id="btnGuardar" class="btn btn-primary">
            Registrar cliente
        </button>
    </div>
</form>
</div>

@endsection


@push('scripts')
<script>
// ── Botón submit: deshabilitar DESPUÉS de que el navegador valide ──
document.getElementById('frmCliente').addEventListener('submit', function(e) {
    // Solo deshabilitar si el form pasa la validación HTML5
    if (!this.checkValidity()) return; // el navegador mostrará el error
    var btn = document.getElementById('btnGuardar');
    setTimeout(function() {
        btn.disabled = true;
        btn.textContent = 'Guardando…';
    }, 10);
});

// ── Google Maps (opcional — no bloquea el form) ────────────────
let mapInitialized = false;

function initMap() {
    try {
        const latInput = document.getElementById('latitud');
        const lngInput = document.getElementById('longitud');
        const direccionInput = document.getElementById('direccion');

        const defaultLocation = { lat: 17.220609, lng: -100.630392 };
        const initialLocation = (latInput.value && lngInput.value)
            ? { lat: parseFloat(latInput.value), lng: parseFloat(lngInput.value) }
            : defaultLocation;

        // Remove placeholder text
        const placeholder = document.getElementById('map-placeholder');
        if (placeholder) placeholder.remove();

        const map = new google.maps.Map(document.getElementById('map'), {
            center: initialLocation,
            zoom: 15,
        });

        const geocoder = new google.maps.Geocoder();

        const marker = new google.maps.Marker({
            position: initialLocation,
            map: map,
            draggable: true,
        });

        if (!latInput.value) {
            latInput.value = initialLocation.lat;
            lngInput.value = initialLocation.lng;
        }

        // Autocomplete en dirección
        const autocomplete = new google.maps.places.Autocomplete(direccionInput, {
            componentRestrictions: { country: 'mx' },
            fields: ['formatted_address', 'geometry', 'name'],
        });

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (!place.geometry?.location) return;
            const loc = place.geometry.location;
            map.setCenter(loc); map.setZoom(17);
            marker.setPosition(loc);
            latInput.value = loc.lat();
            lngInput.value = loc.lng();
            direccionInput.value = place.formatted_address || place.name;
        });

        function setLocation(latLng) {
            marker.setPosition(latLng);
            latInput.value = latLng.lat();
            lngInput.value = latLng.lng();
            geocoder.geocode({ location: latLng }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    direccionInput.value = results[0].formatted_address;
                }
            });
        }

        map.addListener('click',       e => setLocation(e.latLng));
        marker.addListener('dragend',  e => setLocation(e.latLng));

        mapInitialized = true;
    } catch(err) {
        console.warn('Google Maps no pudo inicializar:', err);
        document.getElementById('map').innerHTML =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:#9ca3af">Mapa no disponible — el formulario funciona sin él.</div>';
    }
}

// Si el script de Maps falla en cargar, limpiar el placeholder
window.addEventListener('load', function() {
    if (!mapInitialized) {
        const ph = document.getElementById('map-placeholder');
        if (ph) ph.textContent = 'Mapa no disponible — el formulario funciona sin él.';
    }
});
</script>

<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfb3MRYco1aN4yaJyXmK8jperHTMJl07E&libraries=places&callback=initMap"
    onerror="document.getElementById('map').innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:#9ca3af\'>Mapa no disponible</div>'">
</script>

@endpush
