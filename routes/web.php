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

// ── Owner ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard',                   [\App\Http\Controllers\OwnerController::class, 'index'])          ->name('dashboard');
    Route::get('/admins/crear',                [\App\Http\Controllers\OwnerController::class, 'create'])         ->name('admins.create');
    Route::post('/admins',                     [\App\Http\Controllers\OwnerController::class, 'store'])          ->name('admins.store');
    Route::post('/admins/{id}/toggle',         [\App\Http\Controllers\OwnerController::class, 'toggle'])         ->name('admins.toggle');
    Route::post('/admins/{id}/reset-password', [\App\Http\Controllers\OwnerController::class, 'resetPassword'])  ->name('admins.resetPassword');
    Route::put('/admins/{id}',                 [\App\Http\Controllers\OwnerController::class, 'update'])         ->name('admins.update');
    Route::delete('/admins/{id}',              [\App\Http\Controllers\OwnerController::class, 'destroy'])        ->name('admins.destroy');
    Route::post('/admins/{id}/notas',          [\App\Http\Controllers\OwnerController::class, 'storeNota'])      ->name('admins.notas.store');
    Route::delete('/admins/{id}/notas/{nota}', [\App\Http\Controllers\OwnerController::class, 'destroyNota'])   ->name('admins.notas.destroy');
    Route::post('/perfil/password',            [\App\Http\Controllers\OwnerController::class, 'changeOwnPassword'])->name('perfil.password');
});

// ── Admin only: dashboard, empleados, reportes ───────────────────────
Route::middleware(['auth', 'role:admin'])->group(function () {
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
    Route::get('/clientes',               [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/crear',         [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('/clientes',              [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{id}',          [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{id}/editar',   [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{id}',          [ClienteController::class, 'update'])->name('clientes.update');

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
    Route::post('/prestamos/{id}/cobro-extra',                   [PagoController::class, 'registrarExtra'])->name('prestamos.cobroExtra');
    Route::post('/prestamos/{id}/pagar-cuota',                   [PagoController::class, 'pagarCuota'])->name('prestamos.pagarCuota');
    Route::post('/prestamos/{id}/agendar-cobro',                 [PagoController::class, 'agendarCobro'])->name('prestamos.agendarCobro');
    Route::post('/prestamos/{id}/payment-hold',                  [PagoController::class, 'togglePaymentHold'])->name('prestamos.paymentHold');
    Route::post('/prestamos/{id}/actualizar-frecuencia',         [PrestamoController::class, 'actualizarFrecuencia'])->name('prestamos.actualizarFrecuencia');
    Route::post('/prestamos/{id}/asignarme',                     [PagoController::class, 'asignarme'])->name('prestamos.asignarme');
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
    Route::get('/desembolsos',               [DesembolsoController::class, 'index'])->name('desembolsos.index');
    Route::post('/desembolsos/confirmar',    [DesembolsoController::class, 'confirmar'])->name('desembolsos.confirmar');
});

// ── Cobros: admin + promo + collector ────────────────────────────────
Route::middleware(['auth', 'role:collector,admin,promo'])->group(function () {
    Route::get('/cobros',              [PagoController::class, 'index'])->name('cobros.index');
    Route::post('/cobros/registrar',   [PagoController::class, 'registrar'])->name('cobros.registrar');
});

// ── Monitor de cobros por cobrador (solo admin) ───────────────────────
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/cobros/monitor', [PagoController::class, 'monitor'])->name('cobros.monitor');
});

// ── Ver cliente: collector + admin + promo ───────────────────────────
Route::middleware(['auth', 'role:collector,admin,promo'])->group(function () {
    Route::get('/clientes/{id}/cobrador', [ClienteController::class, 'showCobrador'])->name('clientes.showCobrador');
});
