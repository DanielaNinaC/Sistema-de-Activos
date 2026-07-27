<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas para usuarios no autenticados (Invitados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/captcha-image', [AuthController::class, 'generateCaptchaImage'])->name('captcha.image');
});

// Rutas protegidas (Requieren sesión activa)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/perfil/actualizar', [App\Http\Controllers\PerfilController::class, 'update'])->name('perfil.update');

        // Administración - pantallas de administración
        Route::prefix('admin')->name('admin.')->group(function(){
            Route::get('/', [App\Http\Controllers\Admin\AdministracionController::class, 'index'])->name('administracion.index');
            Route::get('/activos/inventario', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'index'])->name('activos.inventario');
            Route::get('/activos/registrar-activo', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'create'])->name('activos.registrar');
            Route::post('/activos/inventario', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'store'])->name('activos.inventario.store');
            Route::post('/activos/registrar-activo', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'store'])->name('activos.registrar.store');
            Route::get('/activos/categorias', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'categorias'])->name('activos.categorias');
            Route::post('/activos/categorias', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'storeCategoria'])->name('activos.categorias.store');
            Route::get('/activos/ubicaciones', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'ubicaciones'])->name('activos.ubicaciones');
            Route::post('/activos/ubicaciones', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'storeUbicacion'])->name('activos.ubicaciones.store');
            Route::get('/incidentes/registrar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesIndex'])->name('incidentes.registrar');
            Route::post('/incidentes/registrar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesStore'])->name('incidentes.registrar.store');
            Route::get('/incidentes/historial', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesHistorial'])->name('incidentes.historial');
            Route::get('/mantenimiento/programar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoIndex'])->name('mantenimiento.programar');
            Route::post('/mantenimiento/programar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoStore'])->name('mantenimiento.programar.store');
            Route::get('/mantenimiento/historial', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoHistorial'])->name('mantenimiento.historial');
            Route::get('/riesgos/modos-falla', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'modosIndex'])->name('riesgos.modos');
            Route::post('/riesgos/modos-falla', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'modosStore'])->name('riesgos.modos.store');
            Route::get('/riesgos/evaluacion-fmea', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'evaluacionIndex'])->name('riesgos.evaluacion');
            Route::post('/riesgos/evaluacion-fmea', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'evaluacionStore'])->name('riesgos.evaluacion.store');
            Route::get('/riesgos/score', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'pesosIndex'])->name('riesgos.score');
            Route::post('/riesgos/score', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'pesosStore'])->name('riesgos.score.store');
            Route::get('/riesgos/riesgo', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'riesgoIndex'])->name('riesgos.riesgo');
            Route::get('/riesgos/alertas', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'alertasIndex'])->name('riesgos.alertas');
            Route::post('/riesgos/alertas', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'alertasStore'])->name('riesgos.alertas.store');
            Route::get('/riesgos/plan-tratamiento', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'tratamientoIndex'])->name('riesgos.tratamiento');
            Route::post('/riesgos/plan-tratamiento', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'tratamientoStore'])->name('riesgos.tratamiento.store');
            Route::resource('usuarios', App\Http\Controllers\Admin\UsuarioController::class);
            Route::get('/roles', [App\Http\Controllers\Admin\RolController::class, 'index'])->name('roles.index');
            Route::get('/logs', [App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs.index');
        });
});
