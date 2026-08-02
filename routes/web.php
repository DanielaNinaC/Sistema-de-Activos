<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas para usuarios no autenticados (Invitados)
Route::middleware(['web'])->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register'])->name('register');
    });
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

            // Secciones restringidas a administradores (coincide con lo ya ocultado en el menú lateral)
            Route::middleware('admin')->group(function () {
                Route::get('/activos/inventario', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'index'])->name('activos.inventario');
                Route::get('/activos/detalles/{id}', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'show'])->name('activos.show');
                Route::get('/activos/editar/{id}', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'edit'])->name('activos.edit');
                Route::put('/activos/actualizar/{id}', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'update'])->name('activos.update');
                Route::get('/activos/registrar-activo', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'create'])->name('activos.registrar');
                Route::post('/activos/inventario', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'store'])->name('activos.inventario.store');
                Route::post('/activos/registrar-activo', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'store'])->name('activos.registrar.store');
                Route::get('/activos/categorias', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'categorias'])->name('activos.categorias');
                Route::get('/activos/categorias/{id}/equipos', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'activosPorCategoria'])->name('activos.categorias.equipos');
                Route::post('/activos/categorias', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'storeCategoria'])->name('activos.categorias.store');
                Route::put('/activos/categorias/{id}', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'updateCategoria'])->name('activos.categorias.update');
                Route::get('/activos/ubicaciones', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'ubicaciones'])->name('activos.ubicaciones');
                Route::get('/activos/ubicaciones/{id}/equipos', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'activosPorUbicacion'])->name('activos.ubicaciones.equipos');
                Route::post('/activos/ubicaciones', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'storeUbicacion'])->name('activos.ubicaciones.store');
                Route::put('/activos/ubicaciones/{id}', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'updateUbicacion'])->name('activos.ubicaciones.update');
                Route::get('/incidentes/registrar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesIndex'])->name('incidentes.registrar');
                Route::post('/incidentes/registrar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesStore'])->name('incidentes.registrar.store');
                Route::get('/incidentes/seguimiento', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesSeguimiento'])->name('incidentes.seguimiento');
                Route::get('/incidentes/historial', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesHistorial'])->name('incidentes.historial');
                Route::get('/incidentes/detalles/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesShow'])->name('incidentes.show');
                Route::put('/incidentes/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesUpdate'])->name('incidentes.update');
                Route::get('/mantenimiento/programar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoIndex'])->name('mantenimiento.programar');
                Route::post('/mantenimiento/programar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoStore'])->name('mantenimiento.programar.store');
                Route::get('/mantenimiento/cronograma', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoCronograma'])->name('mantenimiento.cronograma');
                Route::get('/mantenimiento/historial', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoHistorial'])->name('mantenimiento.historial');
                Route::get('/mantenimiento/detalles/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoShow'])->name('mantenimiento.show');
                Route::put('/mantenimiento/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoUpdate'])->name('mantenimiento.update');

                Route::resource('usuarios', App\Http\Controllers\Admin\UsuarioController::class);
                Route::get('/roles', [App\Http\Controllers\Admin\RolController::class, 'index'])->name('roles.index');
                Route::get('/logs', [App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs.index');
            });

            Route::get('/riesgos/modos-falla', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'modosIndex'])->name('riesgos.modos');
            Route::post('/riesgos/modos-falla', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'modosStore'])->name('riesgos.modos.store');
            Route::put('/riesgos/modos-falla/{id}', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'modosUpdate'])->name('riesgos.modos.update');
            Route::get('/riesgos/evaluacion-fmea', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'evaluacionIndex'])->name('riesgos.evaluacion');
            Route::post('/riesgos/evaluacion-fmea', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'evaluacionStore'])->name('riesgos.evaluacion.store');
            Route::post('/riesgos/evaluar-activo', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'evaluarActivo'])->name('riesgos.evaluar.activo');
            Route::post('/riesgos/evaluar-todos', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'evaluarTodos'])->name('riesgos.evaluar.todos');
            Route::get('/riesgos/score', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'pesosIndex'])->name('riesgos.score');
            Route::post('/riesgos/score', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'pesosStore'])->name('riesgos.score.store');
            Route::get('/riesgos/riesgo', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'riesgoIndex'])->name('riesgos.riesgo');
            Route::get('/riesgos/alertas', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'alertasIndex'])->name('riesgos.alertas');
            Route::post('/riesgos/alertas', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'alertasStore'])->name('riesgos.alertas.store');
            Route::get('/riesgos/plan-tratamiento', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'tratamientoIndex'])->name('riesgos.tratamiento');
            Route::post('/riesgos/plan-tratamiento', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'tratamientoStore'])->name('riesgos.tratamiento.store');
        });
});
