<?php

use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpresaAdminController;
use App\Http\Controllers\PeriodoAdminController;
use App\Http\Controllers\DocumentoCompraController;
use App\Http\Controllers\ImportacionComprasController;
use App\Http\Controllers\BoletaHonorarioController;
use App\Http\Controllers\ImportacionHonorariosController;
use App\Http\Controllers\RemuneracionController;
use App\Http\Controllers\ImportacionRemuneracionesController;
use App\Http\Controllers\ImportacionDiarioController;

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
    Route::get('/reportes/libro-mayor', [App\Http\Controllers\LibroMayorController::class, 'index'])
        ->name('reportes.libro-mayor');
    Route::get('/reportes/libro-mayor/{cuenta}', [App\Http\Controllers\LibroMayorController::class, 'cuenta'])
        ->name('reportes.libro-mayor.cuenta');
    Route::get('/reportes/balance-comprobacion', [App\Http\Controllers\BalanceComprobacionController::class, 'index'])
        ->name('reportes.balance-comprobacion');

    Route::get('/empresas/{empresa}/compras', [DocumentoCompraController::class, 'index'])->name('compras.index');
    Route::post('/empresas/{empresa}/compras', [DocumentoCompraController::class, 'store'])->name('compras.store');
    Route::delete('/empresas/{empresa}/compras/{documento}', [DocumentoCompraController::class, 'destroy'])->name('compras.destroy');
    Route::post('/empresas/{empresa}/compras/{documento}/cuenta', [DocumentoCompraController::class, 'asignarCuenta'])->name('compras.asignar-cuenta');
    Route::post('/empresas/{empresa}/compras/centralizar', [DocumentoCompraController::class, 'centralizar'])->name('compras.centralizar');

    Route::get('/empresas/{empresa}/compras/importar', [ImportacionComprasController::class, 'form'])->name('compras.importar');
    Route::post('/empresas/{empresa}/compras/importar/previsualizar', [ImportacionComprasController::class, 'previsualizar'])->name('compras.importar.previsualizar');
    Route::post('/empresas/{empresa}/compras/importar/confirmar', [ImportacionComprasController::class, 'confirmar'])->name('compras.importar.confirmar');
    // Honorarios
    Route::get('/empresas/{empresa}/honorarios', [BoletaHonorarioController::class, 'index'])->name('honorarios.index');
    Route::post('/empresas/{empresa}/honorarios', [BoletaHonorarioController::class, 'store'])->name('honorarios.store');
    Route::delete('/empresas/{empresa}/honorarios/{boleta}', [BoletaHonorarioController::class, 'destroy'])->name('honorarios.destroy');
    Route::post('/empresas/{empresa}/honorarios/{boleta}/cuenta', [BoletaHonorarioController::class, 'asignarCuenta'])->name('honorarios.asignar-cuenta');
    Route::post('/empresas/{empresa}/honorarios/centralizar', [BoletaHonorarioController::class, 'centralizar'])->name('honorarios.centralizar');

    Route::get('/empresas/{empresa}/honorarios/importar', [ImportacionHonorariosController::class, 'form'])->name('honorarios.importar');
    Route::post('/empresas/{empresa}/honorarios/importar/previsualizar', [ImportacionHonorariosController::class, 'previsualizar'])->name('honorarios.importar.previsualizar');
    Route::post('/empresas/{empresa}/honorarios/importar/confirmar', [ImportacionHonorariosController::class, 'confirmar'])->name('honorarios.importar.confirmar');

    // Remuneraciones
    Route::get('/empresas/{empresa}/remuneraciones', [RemuneracionController::class, 'index'])->name('remuneraciones.index');
    Route::delete('/empresas/{empresa}/remuneraciones/{trabajador}', [RemuneracionController::class, 'destroy'])->name('remuneraciones.destroy');
    Route::post('/empresas/{empresa}/remuneraciones/centralizar', [RemuneracionController::class, 'centralizar'])->name('remuneraciones.centralizar');
    Route::get('/empresas/{empresa}/remuneraciones/importar', [ImportacionRemuneracionesController::class, 'form'])->name('remuneraciones.importar');
    Route::post('/empresas/{empresa}/remuneraciones/importar', [ImportacionRemuneracionesController::class, 'importar'])->name('remuneraciones.importar.subir');
    
    // Importador del Diario histórico
    Route::get('/empresas/{empresa}/diario/importar', [ImportacionDiarioController::class, 'form'])->name('diario.importar');
    Route::post('/empresas/{empresa}/diario/importar/previsualizar', [ImportacionDiarioController::class, 'previsualizar'])->name('diario.importar.previsualizar');
    Route::post('/empresas/{empresa}/diario/importar/confirmar', [ImportacionDiarioController::class, 'confirmar'])->name('diario.importar.confirmar');

    // Empresas (administración)
    Route::get('/empresas', [EmpresaAdminController::class, 'index'])->name('empresas.index');
    Route::get('/empresas/crear', [EmpresaAdminController::class, 'create'])->name('empresas.create');
    Route::post('/empresas', [EmpresaAdminController::class, 'store'])->name('empresas.store');
    Route::get('/empresas/{empresa}', [EmpresaAdminController::class, 'show'])->name('empresas.show');
    Route::get('/empresas/{empresa}/editar', [EmpresaAdminController::class, 'edit'])->name('empresas.edit');
    Route::put('/empresas/{empresa}', [EmpresaAdminController::class, 'update'])->name('empresas.update');
    Route::post('/empresas/{empresa}/toggle-activa', [EmpresaAdminController::class, 'toggleActiva'])->name('empresas.toggle-activa');
    Route::post('/empresas/{empresa}/instalar-plan', [EmpresaAdminController::class, 'instalarPlan'])->name('empresas.instalar-plan');

    // Períodos (dentro del contexto de una empresa)
    Route::post('/empresas/{empresa}/periodos/abrir', [PeriodoAdminController::class, 'abrir'])->name('periodos.abrir');
    Route::post('/empresas/{empresa}/periodos/{periodo}/bloquear', [PeriodoAdminController::class, 'bloquear'])->name('periodos.bloquear');
    Route::post('/empresas/{empresa}/periodos/{periodo}/cerrar', [PeriodoAdminController::class, 'cerrar'])->name('periodos.cerrar');
    Route::post('/empresas/{empresa}/periodos/{periodo}/reabrir', [PeriodoAdminController::class, 'reabrir'])->name('periodos.reabrir');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
