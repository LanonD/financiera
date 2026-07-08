<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\DesembolsoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/',       [AuthController::class, 'showLogin'])->name('login');
Route::get('/login',  [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout')->middleware('auth');
// Navegar directo a /logout (URL tecleada o autocompletada) también cierra sesión
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth');

// ── Owner ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard',                   [\App\Http\Controllers\OwnerController::class, 'index'])          ->name('dashboard');
    Route::get('/rendimientos',                [\App\Http\Controllers\OwnerController::class, 'rendimientos'])   ->name('rendimientos');
    Route::get('/financiamientos',             [\App\Http\Controllers\FinanciamientoController::class, 'index'])           ->name('financiamientos.index');
    Route::post('/financiamientos/tour-visto', [\App\Http\Controllers\FinanciamientoController::class, 'tourVisto'])       ->name('financiamientos.tourVisto');
    Route::post('/financiamientos',            [\App\Http\Controllers\FinanciamientoController::class, 'store'])           ->name('financiamientos.store');
    Route::put('/financiamientos/{id}',        [\App\Http\Controllers\FinanciamientoController::class, 'update'])          ->name('financiamientos.update');
    Route::post('/financiamientos/{id}/toggle',[\App\Http\Controllers\FinanciamientoController::class, 'toggle'])          ->name('financiamientos.toggle');
    Route::delete('/financiamientos/{id}',     [\App\Http\Controllers\FinanciamientoController::class, 'destroy'])         ->name('financiamientos.destroy');
    Route::post('/financiamientos/{id}/inversores',               [\App\Http\Controllers\FinanciamientoController::class, 'storeInversor'])    ->name('financiamientos.inversores.store');
    Route::put('/financiamientos/{id}/inversores/{inversor}',     [\App\Http\Controllers\FinanciamientoController::class, 'updateInversor'])   ->name('financiamientos.inversores.update');
    Route::post('/financiamientos/{id}/inversores/{inversor}/salida',[\App\Http\Controllers\FinanciamientoController::class, 'salidaInversor'])->name('financiamientos.inversores.salida');
    Route::post('/financiamientos/{id}/movimientos',              [\App\Http\Controllers\FinanciamientoController::class, 'storeMovimiento'])  ->name('financiamientos.movimientos.store');
    Route::delete('/financiamientos/{id}/movimientos/{movimiento}',[\App\Http\Controllers\FinanciamientoController::class, 'destroyMovimiento'])->name('financiamientos.movimientos.destroy');
    Route::get('/admins/crear',                [\App\Http\Controllers\OwnerController::class, 'create'])         ->name('admins.create');
    Route::get('/admins/{id}',                 [\App\Http\Controllers\OwnerController::class, 'show'])            ->name('admins.show');
    Route::get('/admins/{id}/flujo',           [\App\Http\Controllers\OwnerController::class, 'flujoMensual'])   ->name('admins.flujo');
    Route::post('/admins',                     [\App\Http\Controllers\OwnerController::class, 'store'])          ->name('admins.store');
    Route::post('/admins/{id}/toggle',         [\App\Http\Controllers\OwnerController::class, 'toggle'])         ->name('admins.toggle');
    Route::post('/admins/{id}/reset-password', [\App\Http\Controllers\OwnerController::class, 'resetPassword'])  ->name('admins.resetPassword');
    Route::put('/admins/{id}',                 [\App\Http\Controllers\OwnerController::class, 'update'])         ->name('admins.update');
    Route::delete('/admins/{id}',              [\App\Http\Controllers\OwnerController::class, 'destroy'])        ->name('admins.destroy');
    Route::post('/admins/{id}/notas',          [\App\Http\Controllers\OwnerController::class, 'storeNota'])      ->name('admins.notas.store');
    Route::delete('/admins/{id}/notas/{nota}', [\App\Http\Controllers\OwnerController::class, 'destroyNota'])   ->name('admins.notas.destroy');
    Route::post('/perfil/password',            [\App\Http\Controllers\OwnerController::class, 'changeOwnPassword'])->name('perfil.password');
});

// ── Supervisor de admins: cobro del rendimiento de financiamientos ──
Route::middleware(['auth', 'role:supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::get('/cobros',                    [\App\Http\Controllers\SupervisorController::class, 'index'])            ->name('cobros.index');
    Route::post('/cobros/{id}/rendimiento',  [\App\Http\Controllers\SupervisorController::class, 'storeRendimiento']) ->name('cobros.rendimiento');
});

// ── Admin only: dashboard, empleados, reportes ───────────────────────
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin financiado: selector de cartera, cambio de cartera activa y
    // detalle del financiamiento (las 2 carteras no comparten información)
    Route::get('/carteras',                  [\App\Http\Controllers\CarteraController::class, 'seleccion']) ->name('carteras.seleccion');
    Route::get('/carteras/entrar/{cartera}', [\App\Http\Controllers\CarteraController::class, 'entrar'])    ->name('carteras.entrar')->whereIn('cartera', ['propia', 'financiada']);
    Route::get('/carteras/financiada',       [\App\Http\Controllers\CarteraController::class, 'financiada'])->name('carteras.financiada');

    Route::get('/dashboard',               [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/empleados',               [EmpleadoController::class, 'index'])->name('empleados.index');
    Route::get('/empleados/crear',         [EmpleadoController::class, 'create'])->name('empleados.create');
    Route::post('/empleados',              [EmpleadoController::class, 'store'])->name('empleados.store');
    Route::get('/empleados/{id}',          [EmpleadoController::class, 'show'])->name('empleados.show');
    Route::get('/empleados/{id}/editar',   [EmpleadoController::class, 'edit'])->name('empleados.edit');
    Route::put('/empleados/{id}',          [EmpleadoController::class, 'update'])->name('empleados.update');
    Route::delete('/empleados/{id}',       [EmpleadoController::class, 'destroy'])->name('empleados.destroy');
    Route::get('/reportes',                [ReporteController::class, 'index'])->name('reportes.index');
});

// ── Admin + Promo: clientes, préstamos, cobros asignar, búsqueda ─────
Route::middleware(['auth', 'role:admin,promo'])->group(function () {
    // Clientes
    Route::get('/clientes',                       [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/crear',                 [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('/clientes',                      [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/nuevo-con-prestamo',    [ClienteController::class, 'createWithPrestamo'])->name('clientes.create_with_prestamo');
    Route::post('/clientes/nuevo-con-prestamo',   [ClienteController::class, 'storeWithPrestamo'])->name('clientes.store_with_prestamo');
    Route::get('/clientes/{id}',                  [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{id}/editar',           [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{id}',                  [ClienteController::class, 'update'])->name('clientes.update');

    // Préstamos
    Route::get('/prestamos',                                     [PrestamoController::class, 'index'])->name('prestamos.index');
    Route::get('/prestamos/nuevo',                               [PrestamoController::class, 'create'])->name('prestamos.create');
    Route::post('/prestamos',                                    [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::get('/prestamos/{id}',                                [PrestamoController::class, 'show'])->name('prestamos.show');
    Route::get('/prestamos/{id}/editar',                         [PrestamoController::class, 'edit'])->name('prestamos.edit');
    Route::put('/prestamos/{id}',                                [PrestamoController::class, 'update'])->name('prestamos.update');
    Route::post('/prestamos/{id}/toggle-interes',                [PrestamoController::class, 'toggleInteres'])->name('prestamos.toggleInteres');
    Route::post('/prestamos/{id}/toggle-mora',                   [PrestamoController::class, 'toggleMora'])->name('prestamos.toggleMora');
    Route::post('/prestamos/{id}/set-mora',                      [PrestamoController::class, 'setMora'])->name('prestamos.setMora');
    Route::post('/prestamos/{id}/campos',                        [PrestamoController::class, 'updateCampos'])->name('prestamos.campos');
    Route::post('/prestamos/{id}/cancelar',                      [PrestamoController::class, 'cancelar'])->name('prestamos.cancelar');
    Route::post('/prestamos/{id}/refinanciar',                   [PrestamoController::class, 'refinanciar'])->name('prestamos.refinanciar');
    Route::post('/prestamos/{id}/cobro-extra',                   [PagoController::class, 'registrarExtra'])->name('prestamos.cobroExtra');
    Route::post('/prestamos/{id}/pagar-cuota',                   [PagoController::class, 'pagarCuota'])->name('prestamos.pagarCuota');
    Route::post('/prestamos/{id}/ponerse-al-dia',                [PagoController::class, 'ponerseAlDia'])->name('prestamos.ponerseAlDia');
    Route::post('/prestamos/{id}/agendar-cobro',                 [PagoController::class, 'agendarCobro'])->name('prestamos.agendarCobro');
    Route::post('/prestamos/{id}/payment-hold',                  [PagoController::class, 'togglePaymentHold'])->name('prestamos.paymentHold');
    Route::post('/prestamos/{id}/actualizar-frecuencia',         [PrestamoController::class, 'actualizarFrecuencia'])->name('prestamos.actualizarFrecuencia');
    Route::post('/prestamos/{id}/asignarme',                     [PagoController::class, 'asignarme'])->name('prestamos.asignarme');
    Route::post('/prestamos/{id}/archivos',                      [PrestamoController::class, 'subirArchivo'])->name('prestamos.archivos.subir');
    Route::delete('/prestamos/{id}/archivos/{archivoId}',        [PrestamoController::class, 'eliminarArchivo'])->name('prestamos.archivos.eliminar');
    Route::post('/prestamos/calcular',                           [PrestamoController::class, 'calcular'])->name('prestamos.calcular');
    Route::post('/prestamos/calcular2',                          [PrestamoController::class, 'calcular2'])->name('prestamos.calcular2');

    // Cobros — asignación
    Route::get('/cobros/asignar',  [PagoController::class, 'asignar'])->name('cobros.asignar');
    Route::post('/cobros/asignar', [PagoController::class, 'guardarAsignacion'])->name('cobros.guardarAsignacion');

    // Búsqueda
    Route::get('/busqueda', [SearchController::class, 'index'])->name('busqueda.index');
});

// ── Desembolsos: admin + promo + desembolso ──────────────────────────
Route::middleware(['auth', 'role:admin,promo,desembolso'])->group(function () {
    Route::get('/desembolsos',                      [DesembolsoController::class, 'index'])->name('desembolsos.index');
    Route::post('/desembolsos/{id}/asignar',        [DesembolsoController::class, 'asignar'])->name('desembolsos.asignar');
    Route::post('/desembolsos/confirmar',           [DesembolsoController::class, 'confirmar'])->name('desembolsos.confirmar');
});

// ── Cobros: admin + promo + collector ────────────────────────────────
Route::middleware(['auth', 'role:collector,admin,promo'])->group(function () {
    Route::get('/cobros',              [PagoController::class, 'index'])->name('cobros.index');
    Route::post('/cobros/registrar',   [PagoController::class, 'registrar'])->name('cobros.registrar');
});

// ── Monitor de cobros por cobrador (admin y promo) ───────────────────
Route::middleware(['auth', 'role:admin,promo'])->group(function () {
    Route::get('/cobros/monitor', [PagoController::class, 'monitor'])->name('cobros.monitor');
});

// ── Ver cliente: collector + admin + promo ───────────────────────────
Route::middleware(['auth', 'role:collector,admin,promo'])->group(function () {
    Route::get('/clientes/{id}/cobrador', [ClienteController::class, 'showCobrador'])->name('clientes.showCobrador');
});
