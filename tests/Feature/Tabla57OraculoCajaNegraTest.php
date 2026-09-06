<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\ModoFalla;
use App\Models\PesoAhp;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tabla 57 — Matriz de Verificación del Algoritmo de Scoring Ponderado
 * (Pruebas de Caja Negra).
 *
 * Ejecuta los 11 casos de prueba de la Tabla 57 contra el sistema real, a
 * través del endpoint HTTP de Evaluación FMEA manual
 * (POST /admin/riesgos/evaluacion-fmea → ProcesoRiesgoController::evaluacionStore),
 * que es el punto de entrada donde S, F y MSR se introducen a mano y el
 * sistema devuelve Score Final y Nivel de Riesgo.
 *
 * Se usa la evaluación manual —y no el motor automatizado
 * (evaluarActivo)— porque la técnica aplicada es análisis de valores
 * límite: el motor automatizado deriva F de un mapeo discreto
 * ({2, 6, 9}) y S/MSR del catálogo de modos de falla, con lo cual los
 * límites exactos de la Tabla 29 (3.00, 6.00, 8.00) son inalcanzables.
 * Ambos caminos comparten el mismo MotorFmeaService::calcularScore() y
 * obtenerNivelYAlerta(), por lo que la verificación aplica al algoritmo
 * en los dos casos.
 *
 * El resultado se vuelca en storage/app/tabla57_resultados.json para
 * trasladarlo a la hoja de cálculo del oráculo.
 */
class Tabla57OraculoCajaNegraTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Los 11 casos de la Tabla 57:
     * [id, S, F, MSR, dependencia, score esperado, nivel esperado].
     *
     * La dependencia de cada caso es la ya fijada en la columna F de la hoja.
     * Los valores esperados se derivan a mano de las Ecuaciones 3 y 4 con los
     * pesos de las Tablas 32 y 34, independientemente del código.
     *
     * Ninguna combinación se sitúa sobre un empate exacto de redondeo (un
     * score sin redondear terminado en ...5 en el tercer decimal): en esos
     * puntos el resultado depende de la representación binaria del motor de
     * cálculo, y el caso dejaría de verificar el algoritmo para pasar a medir
     * el punto flotante de PHP frente al de Excel.
     */
    private const CASOS = [
        // Valores mínimos absolutos de la escala. Subscore 1.000
        ['CN-01', 1, 1, 1, 'Baja', 1.25, 'Bajo'],
        // Valores máximos absolutos de la escala. Subscore 10.000
        ['CN-02', 10, 10, 10, 'Alta', 9.75, 'Crítico'],
        // Límite Bajo/Medio desde abajo: 2.9825 < 3.00
        ['CN-03', 4, 1, 4, 'Baja', 2.98, 'Bajo'],
        // Límite Bajo/Medio desde arriba: 2.996 -> 3.00 exacto
        ['CN-04', 1, 9, 5, 'Baja', 3.00, 'Medio'],
        // Límite Medio/Alto desde abajo: 5.993 < 6.00
        ['CN-05', 6, 9, 3, 'Media', 5.99, 'Medio'],
        // Límite Medio/Alto desde arriba: 6.014 >= 6.00
        ['CN-06', 6, 7, 7, 'Media', 6.01, 'Alto'],
        // Alto/Crítico, mismo perfil técnico (8/6/9) con dependencia Media
        ['CN-07', 8, 6, 9, 'Media', 7.00, 'Alto'],
        // Alto/Crítico, mismo perfil técnico con dependencia Alta: 7.9965 -> 8.00 exacto
        ['CN-08', 8, 6, 9, 'Alta', 8.00, 'Crítico'],
        // Efecto aislado de la dependencia, mismo perfil técnico (5/5/5), D = Baja
        ['CN-09', 5, 5, 5, 'Baja', 4.25, 'Medio'],
        // Mismo perfil técnico con D = Alta: límite exacto 6.00
        ['CN-10', 5, 5, 5, 'Alta', 6.00, 'Alto'],
        // Redondeo a dos decimales: 2.5175 -> 2.52
        ['CN-11', 1, 4, 1, 'Media', 2.52, 'Bajo'],
    ];

    public function test_tabla_57_casos_de_caja_negra_contra_el_sistema(): void
    {
        // Pesos AHP vigentes documentados en las Tablas 32 y 34.
        PesoAhp::create([
            'peso_severidad' => 0.648,
            'peso_frecuencia' => 0.230,
            'peso_msr' => 0.122,
            'peso_riesgo_tecnico' => 0.750,
            'peso_dependencia' => 0.250,
            'fecha_actualizacion' => now(),
            'vigente' => true,
        ]);

        $usuario = $this->crearAdministrador();
        $this->actingAs($usuario);

        $ubicacion = Ubicacion::create(['seccion' => 'Laboratorio', 'aula' => 'QA-1']);

        // Una categoría por nivel de dependencia institucional (Tabla 34).
        $categorias = [];
        foreach (['Baja', 'Media', 'Alta'] as $nivel) {
            $categorias[$nivel] = Categoria::create([
                'nombre_categoria' => "Categoría dependencia {$nivel}",
                'vida_util_meses' => 60,
                'nivel_dependencia' => $nivel,
            ]);
        }

        $resultados = [];

        foreach (self::CASOS as [$id, $S, $F, $MSR, $nivelDependencia, , ]) {
            $categoria = $categorias[$nivelDependencia];

            $modo = ModoFalla::create([
                'fk_id_categoria' => $categoria->id_categoria,
                'nombre_modo_falla' => "Modo de falla {$id}",
                'valor_severidad_base' => $S,
                'valor_msr_base' => $MSR,
            ]);

            $activo = ActivoTecnologico::create([
                'fk_id_categoria' => $categoria->id_categoria,
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'codigo_patrimonial' => "PAT-{$id}",
                'nombre_equipo' => "Equipo de prueba {$id}",
                'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
            ]);

            // Ejecución real: petición HTTP al formulario de Evaluación FMEA.
            $respuesta = $this->post(route('admin.riesgos.evaluacion.store'), [
                'fk_id_activo' => $activo->id_activo,
                'fk_id_modo_falla' => $modo->id_modo_falla,
                'valor_severidad' => $S,
                'valor_frecuencia' => $F,
                'valor_msr' => $MSR,
                'fecha_evaluacion' => now()->format('Y-m-d'),
            ]);

            $respuesta->assertRedirect(route('admin.riesgos.evaluacion'));
            $respuesta->assertSessionHas('success');

            // Lectura de lo que el sistema realmente persistió y muestra.
            $evaluacion = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                ->where('fk_id_modo_falla', $modo->id_modo_falla)
                ->where('vigente', true)
                ->firstOrFail();

            $resultados[] = [
                'id' => $id,
                'activo' => $activo->nombre_equipo,
                'codigo_patrimonial' => $activo->codigo_patrimonial,
                'modo_falla' => $modo->nombre_modo_falla,
                'dependencia' => $nivelDependencia,
                'S_sistema' => (int) $evaluacion->valor_severidad,
                'F_sistema' => (int) $evaluacion->valor_frecuencia,
                'MSR_sistema' => (int) $evaluacion->valor_msr,
                'score_sistema' => (float) $evaluacion->score_calculado,
                'nivel_sistema' => $evaluacion->nivel_riesgo,
                'criticidad_activo' => $activo->fresh()->criticidad_institucional,
            ];
        }

        file_put_contents(
            storage_path('app/tabla57_resultados.json'),
            json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->assertCount(count(self::CASOS), $resultados);

        foreach (self::CASOS as $i => [$id, $S, $F, $MSR, , $scoreEsperado, $nivelEsperado]) {
            // Las entradas deben persistirse tal cual se enviaron: si el
            // sistema alterara S, F o MSR, el oráculo estaría comparando
            // contra entradas distintas y la verificación no sería válida.
            $this->assertSame($S, $resultados[$i]['S_sistema'], "{$id}: S alterado");
            $this->assertSame($F, $resultados[$i]['F_sistema'], "{$id}: F alterado");
            $this->assertSame($MSR, $resultados[$i]['MSR_sistema'], "{$id}: MSR alterado");

            // El sistema debe reproducir exactamente el valor del oráculo.
            $this->assertSame($scoreEsperado, $resultados[$i]['score_sistema'], "{$id}: Score Final distinto del oráculo");
            $this->assertSame($nivelEsperado, $resultados[$i]['nivel_sistema'], "{$id}: Nivel de Riesgo distinto del oráculo");
        }
    }

    private function crearAdministrador(): Usuario
    {
        $rol = Rol::firstOrCreate(['nombre_rol' => 'Administrador']);

        return Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Auditor',
            'apellido' => 'Caja Negra',
            'correo' => 'caja.negra@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
            'permiso_dashboard' => true,
            'permiso_activos' => true,
            'permiso_incidentes' => true,
            'permiso_riesgos' => true,
            'permiso_mantenimiento' => true,
            'permiso_informes' => true,
            'permiso_usuarios' => true,
        ]);
    }
}
