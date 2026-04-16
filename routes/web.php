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

// Admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/empleados',           [EmpleadoController::class, 'index'])->name('empleados.index');
    Route::get('/empleados/crear',     [EmpleadoController::class, 'create'])->name('empleados.create');
    Route::post('/empleados',          [EmpleadoController::class, 'store'])->name('empleados.store');
    Route::get('/empleados/{id}',      [EmpleadoController::class, 'show'])->name('empleados.show');
    Route::get('/empleados/{id}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
    Route::put('/empleados/{id}',      [EmpleadoController::class, 'update'])->name('empleados.update');
    Route::delete('/empleados/{id}',   [EmpleadoController::class, 'destroy'])->name('empleados.destroy');
    Route::get('/reportes',            [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/cobros/asignar',      [PagoController::class, 'asignar'])->name('cobros.asignar');
    Route::post('/cobros/asignar',     [PagoController::class, 'guardarAsignacion'])->name('cobros.guardarAsignacion');
});

// Admin + Promo
Route::middleware(['auth', 'role:admin,promo'])->group(function () {
    Route::get('/clientes',             [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/crear',       [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('/clientes',            [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{id}',        [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{id}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{id}',        [ClienteController::class, 'update'])->name('clientes.update');
    Route::get('/prestamos',            [PrestamoController::class, 'index'])->name('prestamos.index');
    Route::get('/prestamos/nuevo',      [PrestamoController::class, 'create'])->name('prestamos.create');
    Route::post('/prestamos',           [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::get('/prestamos/{id}',       [PrestamoController::class, 'show'])->name('prestamos.show');
    Route::get('/prestamos/{id}/editar',[PrestamoController::class, 'edit'])->name('prestamos.edit');
    Route::put('/prestamos/{id}',       [PrestamoController::class, 'update'])->name('prestamos.update');
    Route::post('/prestamos/{id}/toggle-interes',     [PrestamoController::class, 'toggleInteres'])->name('prestamos.toggleInteres');
    Route::post('/prestamos/{id}/toggle-mora',        [PrestamoController::class, 'toggleMora'])->name('prestamos.toggleMora');
    Route::post('/prestamos/{id}/set-mora',           [PrestamoController::class, 'setMora'])->name('prestamos.setMora');
    Route::post('/prestamos/calcular',  [PrestamoController::class, 'calcular'])->name('prestamos.calcular');
    Route::post('/prestamos/calcular2', [PrestamoController::class, 'calcular2'])->name('prestamos.calcular2');
    Route::get('/desembolsos',          [DesembolsoController::class, 'index'])->name('desembolsos.index');
    Route::post('/desembolsos/confirmar', [DesembolsoController::class, 'confirmar'])->name('desembolsos.confirmar');
    Route::get('/busqueda',             [SearchController::class, 'index'])->name('busqueda.index');
});

// Collector + Admin + Promo (promo puede cobrar también)
Route::middleware(['auth', 'role:collector,admin,promo'])->group(function () {
    Route::get('/cobros',              [PagoController::class, 'index'])->name('cobros.index');
    Route::post('/cobros/registrar',   [PagoController::class, 'registrar'])->name('cobros.registrar');
});

// Promo / Admin: asignarse como cobrador de un préstamo propio
Route::middleware(['auth', 'role:promo,admin'])->group(function () {
    Route::post('/prestamos/{id}/asignarme', [PagoController::class, 'asignarme'])->name('prestamos.asignarme');
});

// Collector también puede ver detalle de clientes
Route::middleware(['auth', 'role:collector,admin,promo'])->group(function () {
    Route::get('/clientes/{id}/cobrador', [ClienteController::class, 'showCobrador'])->name('clientes.showCobrador');
});
