<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InicioController;

Route::get('/', [InicioController::class, 'index'])->name('inicio');

// Rutas para usuarios no autenticados (Invitados)
Route::middleware(['web'])->group(function () {
    // Endpoint para refrescar el token CSRF (evita errores 419 por inactividad)
    Route::get('/csrf-token', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf.refresh');

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

    // Notificaciones del sistema
    Route::get('/notificaciones/recientes', [App\Http\Controllers\NotificacionController::class, 'recientes'])->name('notificaciones.recientes');
    Route::post('/notificaciones/marcar-leidas', [App\Http\Controllers\NotificacionController::class, 'marcarLeidas'])->name('notificaciones.marcar-leidas');

        // Administración - pantallas de administración
        Route::prefix('admin')->name('admin.')->group(function(){
            Route::get('/', [App\Http\Controllers\Admin\AdministracionController::class, 'index'])->name('administracion.index');

            // ── Activos Tecnológicos ──
            Route::middleware('modulo:activos')->group(function () {
                Route::get('/activos/inventario', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'index'])->name('activos.inventario');
                Route::get('/activos/detalles/{id}', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'show'])->name('activos.show');
                Route::post('/activos/{id}/historial', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'storeHistorial'])->name('activos.historial.store');
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
                Route::get('/activos/inventario/excel', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'exportExcel'])->name('activos.inventario.excel');
                Route::get('/activos/inventario/pdf', [App\Http\Controllers\Admin\ActivoTecnologicoController::class, 'exportPdf'])->name('activos.inventario.pdf');
            });

            // ── Incidentes ──
            Route::middleware('modulo:incidentes')->group(function () {
                Route::get('/incidentes/registrar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesIndex'])->name('incidentes.registrar');
                Route::post('/incidentes/registrar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesStore'])->name('incidentes.registrar.store');
                Route::get('/incidentes/seguimiento', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesSeguimiento'])->name('incidentes.seguimiento');
                Route::get('/incidentes/historial', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesHistorial'])->name('incidentes.historial');
                Route::get('/incidentes/historial/activo/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesHistorialActivo'])->name('incidentes.historial.activo');
                Route::get('/incidentes/detalles/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesShow'])->name('incidentes.show');
                Route::put('/incidentes/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'incidentesUpdate'])->name('incidentes.update');
            });

            // ── Mantenimiento ──
            Route::middleware('modulo:mantenimiento')->group(function () {
                Route::get('/mantenimiento/programar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoIndex'])->name('mantenimiento.programar');
                Route::post('/mantenimiento/programar', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoStore'])->name('mantenimiento.programar.store');
                Route::get('/mantenimiento/cronograma', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoCronograma'])->name('mantenimiento.cronograma');
                Route::get('/mantenimiento/historial', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoHistorial'])->name('mantenimiento.historial');
                Route::get('/mantenimiento/detalles/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoShow'])->name('mantenimiento.show');
                Route::put('/mantenimiento/{id}', [App\Http\Controllers\Admin\ProcesoActivoController::class, 'mantenimientoUpdate'])->name('mantenimiento.update');
            });

            // ── Gestión de Usuarios (requiere permiso_usuarios) ──
            Route::middleware('modulo:usuarios')->group(function () {
                Route::get('/usuarios/verificar-email', [App\Http\Controllers\Admin\UsuarioController::class, 'verificarEmail'])->name('usuarios.verificar-email');
                Route::get('/usuarios/verificar-todos', [App\Http\Controllers\Admin\UsuarioController::class, 'verificarTodos'])->name('usuarios.verificar-todos');
                Route::resource('usuarios', App\Http\Controllers\Admin\UsuarioController::class);
                Route::get('/roles', [App\Http\Controllers\Admin\RolController::class, 'index'])->name('roles.index');
                Route::get('/logs', [App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs.index');
            });

            // ── Evaluación de Riesgos ──
            Route::middleware('modulo:riesgos')->group(function () {
                // Pantalla intermedia que agrupa Modos de Falla y Score (pesos AHP)
                Route::get('/riesgos/configuracion', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'configuracionIndex'])->name('riesgos.configuracion');
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
                Route::put('/riesgos/plan-tratamiento/{id}', [App\Http\Controllers\Admin\ProcesoRiesgoController::class, 'tratamientoUpdate'])->name('riesgos.tratamiento.update');
            });

            // ── Informes y Reportes ──
            Route::middleware('modulo:informes')->prefix('informes')->name('informes.')->group(function () {
                Route::get('/vida-util', [App\Http\Controllers\Admin\InformesController::class, 'vidaUtilRemanente'])->name('vida-util');
                Route::get('/vida-util/pdf', [App\Http\Controllers\Admin\InformesController::class, 'vidaUtilPdf'])->name('vida-util.pdf');
                Route::get('/vida-util/excel', [App\Http\Controllers\Admin\InformesController::class, 'vidaUtilExcel'])->name('vida-util.excel');

                Route::get('/evolucion-score', [App\Http\Controllers\Admin\InformesController::class, 'evolucionScore'])->name('evolucion-score');
                Route::get('/evolucion-score/excel', [App\Http\Controllers\Admin\InformesController::class, 'evolucionScoreExcel'])->name('evolucion-score.excel');

                Route::get('/modos-falla-frecuentes', [App\Http\Controllers\Admin\InformesController::class, 'modosFallaFrecuentes'])->name('modos-falla');

                Route::get('/mantenimiento-tipo', [App\Http\Controllers\Admin\InformesController::class, 'mantenimientoPreventivoCorrectivo'])->name('mantenimiento-tipo');

                Route::get('/sin-tratamiento', [App\Http\Controllers\Admin\InformesController::class, 'evaluacionesSinTratamiento'])->name('sin-tratamiento');
                Route::get('/sin-tratamiento/pdf', [App\Http\Controllers\Admin\InformesController::class, 'evaluacionesSinTratamientoPdf'])->name('sin-tratamiento.pdf');
                Route::get('/sin-tratamiento/excel', [App\Http\Controllers\Admin\InformesController::class, 'evaluacionesSinTratamientoExcel'])->name('sin-tratamiento.excel');

                Route::get('/trazabilidad', [App\Http\Controllers\Admin\InformesController::class, 'trazabilidadUsuarios'])->name('trazabilidad');
                Route::get('/trazabilidad/excel', [App\Http\Controllers\Admin\InformesController::class, 'trazabilidadExcel'])->name('trazabilidad.excel');

                Route::get('/auditoria-consolidado', [App\Http\Controllers\Admin\InformesController::class, 'auditoriaConsolidado'])->name('auditoria');
                Route::get('/auditoria-consolidado/pdf', [App\Http\Controllers\Admin\InformesController::class, 'auditoriaConsolidadoPdf'])->name('auditoria.pdf');

                Route::get('/estado-activos', [App\Http\Controllers\Admin\InformesController::class, 'estadoActivos'])->name('estado-activos');
                Route::get('/estado-activos/pdf', [App\Http\Controllers\Admin\InformesController::class, 'estadoActivosPdf'])->name('estado-activos.pdf');
                Route::get('/estado-activos/excel', [App\Http\Controllers\Admin\InformesController::class, 'estadoActivosExcel'])->name('estado-activos.excel');

                Route::get('/activos-baja', [App\Http\Controllers\Admin\InformesController::class, 'activosDeBaja'])->name('activos-baja');
                Route::get('/activos-baja/pdf', [App\Http\Controllers\Admin\InformesController::class, 'activosDeBajaPdf'])->name('activos-baja.pdf');
                Route::get('/activos-baja/excel', [App\Http\Controllers\Admin\InformesController::class, 'activosDeBajaExcel'])->name('activos-baja.excel');

                Route::get('/historial-tratamiento', [App\Http\Controllers\Admin\InformesController::class, 'historialTratamiento'])->name('historial-tratamiento');

                // ── Reportes para Auditoría de TI (RA-01..RA-07) ──
                Route::prefix('auditoria-ti')->name('auditoria-ti.')->group(function () {
                    Route::get('/ra-01-inventario', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra01Inventario'])->name('ra01');
                    Route::get('/ra-01-inventario/pdf', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra01InventarioPdf'])->name('ra01.pdf');
                    Route::get('/ra-01-inventario/excel', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra01InventarioExcel'])->name('ra01.excel');

                    Route::get('/ra-02-mantenimiento', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra02Mantenimiento'])->name('ra02');
                    Route::get('/ra-02-mantenimiento/pdf', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra02MantenimientoPdf'])->name('ra02.pdf');
                    Route::get('/ra-02-mantenimiento/excel', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra02MantenimientoExcel'])->name('ra02.excel');

                    Route::get('/ra-03-incidentes', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra03Incidentes'])->name('ra03');
                    Route::get('/ra-03-incidentes/pdf', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra03IncidentesPdf'])->name('ra03.pdf');
                    Route::get('/ra-03-incidentes/excel', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra03IncidentesExcel'])->name('ra03.excel');

                    Route::get('/ra-04-criticidad', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra04Criticidad'])->name('ra04');
                    Route::get('/ra-04-criticidad/pdf', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra04CriticidadPdf'])->name('ra04.pdf');
                    Route::get('/ra-04-criticidad/excel', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra04CriticidadExcel'])->name('ra04.excel');

                    Route::get('/ra-05-roles-permisos', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra05RolesPermisos'])->name('ra05');
                    Route::get('/ra-05-roles-permisos/pdf', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra05RolesPermisosPdf'])->name('ra05.pdf');
                    Route::get('/ra-05-roles-permisos/excel', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra05RolesPermisosExcel'])->name('ra05.excel');

                    Route::get('/ra-06-verificacion', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra06Verificacion'])->name('ra06');
                    Route::get('/ra-06-verificacion/pdf', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra06VerificacionPdf'])->name('ra06.pdf');

                    Route::get('/ra-07-activos-red', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra07ActivosRed'])->name('ra07');
                    Route::get('/ra-07-activos-red/pdf', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra07ActivosRedPdf'])->name('ra07.pdf');
                    Route::get('/ra-07-activos-red/excel', [App\Http\Controllers\Admin\AuditoriaReportesController::class, 'ra07ActivosRedExcel'])->name('ra07.excel');
                });
            });

            // ── Auditoría Interna de TI (asistente) ──
            Route::middleware('modulo:informes')->prefix('auditoria-interna')->name('auditoria-interna.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'index'])->name('index');
                Route::get('/crear', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'store'])->name('store');
                Route::get('/{id}', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'show'])->name('show');
                Route::put('/{id}', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'update'])->name('update');
                Route::post('/{id}/generar-textos', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'generarTextos'])->name('generar-textos');
                Route::get('/{id}/pdf', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'exportarPdf'])->name('pdf');
                Route::get('/{id}/excel', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'exportarExcel'])->name('excel');
                Route::post('/{id}/muestra/generar', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'generarMuestra'])->name('muestra.generar');
                Route::put('/{id}/muestra/{muestraId}', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'verificarMuestra'])->name('muestra.verificar');
                Route::post('/{id}/hallazgos', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'hallazgoStore'])->name('hallazgos.store');
                Route::put('/hallazgos/{hallazgoId}', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'hallazgoUpdate'])->name('hallazgos.update');
                Route::put('/seguimiento/{hallazgoId}', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'seguimientoUpdate'])->name('seguimiento.update');
                Route::post('/{id}/enviar-revision', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'enviarRevision'])->name('enviar-revision');
                Route::post('/{id}/aprobar', [App\Http\Controllers\Admin\AuditoriaInternaController::class, 'aprobar'])->name('aprobar');
            });
        });
});
