<?php

use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cambiar la empresa activa de la sesión
    Route::post('/empresa/seleccionar', [EmpresaController::class, 'seleccionar'])
        ->name('empresa.seleccionar');

    // Plan de cuentas (de la empresa activa)
    Route::get('/cuentas', [CuentaController::class, 'index'])->name('cuentas.index');

    // Comprobantes
    // OJO con el orden: /comprobantes/crear debe declararse ANTES de
    // /comprobantes/{comprobante}; si no, Laravel intentaría interpretar
    // "crear" como un ID de comprobante -> 404.
    Route::get('/comprobantes', [ComprobanteController::class, 'index'])
        ->name('comprobantes.index');
    Route::get('/comprobantes/crear', [ComprobanteController::class, 'create'])
        ->name('comprobantes.create');
    Route::post('/comprobantes', [ComprobanteController::class, 'store'])
        ->name('comprobantes.store');
    Route::get('/comprobantes/{comprobante}', [ComprobanteController::class, 'show'])
        ->name('comprobantes.show');
    Route::post('/comprobantes/{comprobante}/aprobar', [ComprobanteController::class, 'aprobar'])
        ->name('comprobantes.aprobar');
    Route::post('/comprobantes/{comprobante}/anular', [ComprobanteController::class, 'anular'])
        ->name('comprobantes.anular');
    Route::delete('/comprobantes/{comprobante}', [ComprobanteController::class, 'eliminar'])
        ->name('comprobantes.eliminar');
        Route::get('/reportes/libro-diario', [App\Http\Controllers\LibroDiarioController::class, 'index'])
        ->name('reportes.libro-diario');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
