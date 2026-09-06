<?php

namespace App\Services;

use App\Models\ActivoTecnologico;
use App\Models\Alerta;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use App\Models\ModoFalla;
use App\Models\PesoAhp;
use App\Models\PlanTratamientoRiesgo;
use App\Models\Usuario;
use Carbon\Carbon;

class MotorFmeaService
{
    /**
     * Mapeo fijo de nivel de Severidad (Tabla 20 de la tesis) a valor numérico.
     */
    public const MAPA_SEVERIDAD = [
        'muy_alta' => 10,
        'alta' => 8,
        'moderada' => 5,
        'baja' => 3,
        'insignificante' => 1,
    ];

    /**
     * Mapeo fijo de nivel de Monitoreo/MSR (Tabla 22/29 de la tesis) a valor
     * numérico. Incluye 'baja' => 7: nivel intermedio entre "no hay ninguna
     * alerta" (muy_baja) y "hay logs pero requieren revisión manual"
     * (moderada) — detección solo por inspección/reporte manual directo,
     * sin registro ni alerta automática.
     */
    public const MAPA_MSR = [
        'muy_baja' => 9,
        'baja' => 7,
        'moderada' => 6,
        'alta' => 4,
        'muy_alta' => 2,
    ];

    /**
     * Escala de Dependencia Institucional (categorias.nivel_dependencia) a [1,10].
     */
    public const MAPA_DEPENDENCIA = [
        'Alta' => 9,
        'Media' => 5,
        'Baja' => 2,
    ];

    /**
     * Sugerencia de tratamiento por nivel de riesgo (columna "Acción del
     * Sistema" de la Tabla 29 de la tesis). Solo Alto/Crítico generan
     * borrador automático: la decisión de tratamiento (sobre todo
     * "Aceptar") es institucional, no un cálculo técnico — el administrador
     * confirma o ajusta lo que el sistema sugiere.
     */
    public const MAPA_TRATAMIENTO_SUGERIDO = [
        'Crítico' => [
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Priorizar atención inmediata; evaluar retiro temporal o intervención urgente del activo.',
            'dias_plazo' => 3,
        ],
        'Alto' => [
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Notificar al administrador, priorizar el mantenimiento e incluir el activo en el seguimiento de riesgos.',
            'dias_plazo' => 15,
        ],
    ];

    /**
     * Nivel de riesgo según el Score calculado (Tabla 29 de la Tesis ISO 31000 / FMEA-MSR)
     */
    public function obtenerNivelYAlerta(float $score): array
    {
        if ($score >= 8.00) {
            return [
                'nivel' => 'Crítico',
                'tipo_alerta' => 'Roja',
                'criticidad' => 'Crítica',
                'color_hex' => '#ef4444',
            ];
        }

        if ($score >= 6.00) {
            return [
                'nivel' => 'Alto',
                'tipo_alerta' => 'Naranja',
                'criticidad' => 'Alta',
                'color_hex' => '#f97316',
            ];
        }

        if ($score >= 3.00) {
            return [
                'nivel' => 'Medio',
                'tipo_alerta' => 'Amarilla',
                'criticidad' => 'Media',
                'color_hex' => '#eab308',
            ];
        }

        return [
            'nivel' => 'Bajo',
            'tipo_alerta' => 'Verde',
            'criticidad' => 'Baja',
            'color_hex' => '#22c55e',
        ];
    }

    /**
     * Interpreta una fecha de adquisición que puede venir como año (4 dígitos)
     * o como fecha completa.
     */
    private function parsearFecha(string $fecha): Carbon
    {
        if (strlen($fecha) === 4 && is_numeric($fecha)) {
            return Carbon::createFromDate((int) $fecha, 1, 1);
        }

        return Carbon::parse($fecha);
    }

    /**
     * Calcula la antigüedad en meses a partir de la fecha de adquisición.
     */
    public function calcularAntiguedadMeses(?string $fechaAdquisicion): int
    {
        if (!$fechaAdquisicion) {
            return 12; // Por defecto 1 año si no hay registro
        }

        try {
            $fecha = $this->parsearFecha($fechaAdquisicion);
            return max(1, (int) $fecha->diffInMonths(now()));
        } catch (\Exception $e) {
            return 12;
        }
    }

    /**
     * Fecha base para calcular el desgaste del activo: la más reciente entre
     * la fecha de adquisición y la del último mantenimiento ya ejecutado
     * ('Finalizado'). Así, completar un mantenimiento real "resetea" el
     * desgaste y puede reducir el riesgo en la siguiente evaluación —cierre
     * del ciclo de Monitoreo y Revisión de ISO 31000.
     */
    public function calcularFechaBaseDesgaste(ActivoTecnologico $activo): Carbon
    {
        $fechaMantenimiento = $activo->fechaUltimoMantenimientoCompletado();

        try {
            $fechaAdquisicion = $activo->fecha_adquisicion
                ? $this->parsearFecha($activo->fecha_adquisicion)
                : now()->subMonths(12);
        } catch (\Exception $e) {
            $fechaAdquisicion = now()->subMonths(12);
        }

        if ($fechaMantenimiento && $fechaMantenimiento->greaterThan($fechaAdquisicion)) {
            return $fechaMantenimiento;
        }

        return $fechaAdquisicion;
    }

    /**
     * Antigüedad en meses de un activo, considerando el reseteo por
     * mantenimiento (ver calcularFechaBaseDesgaste).
     */
    public function calcularAntiguedadMesesActivo(ActivoTecnologico $activo): int
    {
        return max(1, (int) $this->calcularFechaBaseDesgaste($activo)->diffInMonths(now()));
    }

    /**
     * Vida útil remanente de un activo según la vida útil de su categoría.
     * Usa la fecha de adquisición cruda (no la fecha base de desgaste con
     * reseteo por mantenimiento): la vida útil contable de un activo es
     * un dato patrimonial fijo, no algo que un mantenimiento "renueve".
     */
    public function calcularVidaUtilRemanente(ActivoTecnologico $activo): array
    {
        $antiguedadMeses = $this->calcularAntiguedadMeses($activo->fecha_adquisicion);
        $vidaUtilMeses = $activo->categoria->vida_util_meses ?? 60;

        return [
            'antiguedad_meses' => $antiguedadMeses,
            'vida_util_meses' => $vidaUtilMeses,
            'meses_restantes' => $vidaUtilMeses - $antiguedadMeses,
            'porcentaje_consumido' => round(min(100, ($antiguedadMeses / max(1, $vidaUtilMeses)) * 100), 1),
        ];
    }

    /**
     * Único punto de cálculo del Score jerárquico de dos niveles:
     * Nivel 2 (riesgo técnico de falla, matriz Saaty ya validada, CR=0.32%):
     *   SubscoreTecnico = S*w_severidad + F*w_frecuencia + MSR*w_msr
     * Nivel 1 (macro, matriz Saaty 2x2, CR=0):
     *   Score_final = p1*SubscoreTecnico + p2*D_escalado
     * SubscoreTecnico y D están en [1,10] y p1+p2=1, por lo que Score_final
     * queda garantizado en [1,10].
     */
    public function calcularScore(int $S, int $F, int $MSR, int $D): float
    {
        $pesos = PesoAhp::where('vigente', true)->first();

        $wS = (float) ($pesos->peso_severidad ?? 0.648);
        $wF = (float) ($pesos->peso_frecuencia ?? 0.230);
        $wMSR = (float) ($pesos->peso_msr ?? 0.122);
        $p1 = (float) ($pesos->peso_riesgo_tecnico ?? 0.750);
        $p2 = (float) ($pesos->peso_dependencia ?? 0.250);

        $subscoreTecnico = ($S * $wS) + ($F * $wF) + ($MSR * $wMSR);

        return round(($p1 * $subscoreTecnico) + ($p2 * $D), 2);
    }

    /**
     * Cierre de ciclo (Tratamiento → Mantenimiento): solo la estrategia
     * "Mitigar" implica una intervención física sobre el activo, así que es
     * la única que programa un Mantenimiento automáticamente. Transferir,
     * Evitar y Aceptar se cierran cambiando el estado del tratamiento sin
     * ningún Mantenimiento vinculado. Reutiliza id_evaluacion_origen (ya
     * existente en el diccionario de datos) para el vínculo, sin columna
     * nueva. Se invoca tanto desde el borrador automático del motor como
     * desde la creación manual en el controlador, por lo que debe tolerar
     * ejecutarse sin un usuario autenticado (comandos, colas, pruebas).
     */
    public function crearMantenimientoSiMitiga(PlanTratamientoRiesgo $tratamiento): void
    {
        if ($tratamiento->estrategia !== 'Mitigar' || !$tratamiento->evaluacion) {
            return;
        }

        $yaExiste = Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)
            ->whereIn('estado_mantenimiento', ['Programado', 'Pendiente', 'En ejecución'])
            ->exists();

        if ($yaExiste) {
            return;
        }

        $idUsuario = auth()->id() ?? Usuario::first()?->id_usuario;
        if (!$idUsuario) {
            return;
        }

        $idActivo = $tratamiento->evaluacion->fk_id_activo;

        // Correctivo si la falla ya se manifestó (incidente real abierto);
        // Preventivo si el tratamiento se origina solo por desgaste/criticidad.
        $tieneIncidentesAbiertos = Incidente::where('fk_id_activo', $idActivo)
            ->whereNotIn('estado_incidente', ['Resuelto', 'Cerrado', 'resuelto', 'cerrado'])
            ->exists();

        Mantenimiento::create([
            'fk_id_activo' => $idActivo,
            'fk_id_usuario_asignado' => $idUsuario,
            'fk_id_origen' => 2, // Evaluación de riesgos
            'id_evaluacion_origen' => $tratamiento->fk_id_evaluacion,
            'tipo_mantenimiento' => $tieneIncidentesAbiertos ? 'Correctivo' : 'Preventivo',
            'fecha_programada' => $tratamiento->fecha_compromiso ?? now()->addDays(7)->toDateString(),
            'descripcion_trabajo' => $tratamiento->accion_preventiva,
            'estado_mantenimiento' => 'Programado',
        ]);
    }

    /**
     * Ejecuta el motor de inferencia automatizado para un activo específico.
     */
    public function evaluarActivo(int $idActivo, ?array $idsModosFalla = null): array
    {
        $activo = ActivoTecnologico::with(['categoria.modosFalla', 'incidentes', 'mantenimientos'])->findOrFail($idActivo);

        // FASE 1: Lectura de Datos del Inventario (con reseteo por mantenimiento)
        $antiguedadMeses = $this->calcularAntiguedadMesesActivo($activo);
        $vidaUtilMeses = max(1, $activo->categoria->vida_util_meses ?? 60);
        $porcentajeDesgaste = min(100, round(($antiguedadMeses / $vidaUtilMeses) * 100, 2));

        // Modos de falla de la categoría del activo. Si no tiene ninguno
        // definido, NO se evalúa contra modos de otras categorías: se
        // reporta explícitamente y no se generan evaluaciones ni alertas.
        $modosCategoria = $activo->categoria->modosFalla ?? collect();
        if ($modosCategoria->isEmpty()) {
            return [
                'activo' => $activo,
                'antiguedad_meses' => $antiguedadMeses,
                'vida_util_meses' => $vidaUtilMeses,
                'porcentaje_desgaste' => $porcentajeDesgaste,
                'total_incidentes' => $activo->incidentes->count(),
                'max_score' => null,
                'criticidad_global' => null,
                'alerta_global' => null,
                'evaluaciones' => [],
                'sin_modos_falla' => true,
                'evaluacion_parcial' => false,
                'modos_categoria' => 0,
                'modos_conservados' => 0,
            ];
        }

        // Selección granular: con $idsModosFalla se procesan sólo esos pares
        // (activo, modo_falla). Los no incluidos quedan intactos —no se
        // recalculan, conservan su evaluación vigente y no generan alertas—.
        // Sin argumento se evalúan todos, que es el comportamiento por defecto.
        $modosFalla = $idsModosFalla === null
            ? $modosCategoria
            : $modosCategoria->whereIn('id_modo_falla', $idsModosFalla)->values();

        if ($modosFalla->isEmpty()) {
            return [
                'activo' => $activo,
                'antiguedad_meses' => $antiguedadMeses,
                'vida_util_meses' => $vidaUtilMeses,
                'porcentaje_desgaste' => $porcentajeDesgaste,
                'total_incidentes' => $activo->incidentes->count(),
                'max_score' => null,
                'criticidad_global' => null,
                'alerta_global' => null,
                'evaluaciones' => [],
                'sin_modos_falla' => true,
                'evaluacion_parcial' => false,
                'modos_categoria' => $modosCategoria->count(),
                'modos_conservados' => 0,
            ];
        }

        // Dependencia institucional (Nivel 1) según la categoría del activo
        $nivelDependencia = $activo->categoria->nivel_dependencia ?? 'Media';
        $D = self::MAPA_DEPENDENCIA[$nivelDependencia] ?? 5;

        // Cierre del ciclo: si ya hubo un mantenimiento ejecutado, solo
        // cuentan como "recurrencia" los incidentes posteriores a esa
        // intervención. Sin mantenimiento previo, se usa todo el historial.
        $fechaMantenimiento = $activo->fechaUltimoMantenimientoCompletado();
        $incidentesRelevantes = $fechaMantenimiento
            ? $activo->incidentes->filter(fn ($inc) => Carbon::parse($inc->fecha_incidente)->greaterThan($fechaMantenimiento))
            : $activo->incidentes;

        $evaluacionesRealizadas = [];

        foreach ($modosFalla as $modo) {
            // Contar incidentes relevantes del activo para este modo de falla o totales
            $incidentesModo = $incidentesRelevantes
                ->filter(function ($inc) use ($modo) {
                    return str_contains(strtolower($inc->descripcion_falla), strtolower($modo->nombre_modo_falla));
                })->count();

            // Si no hay por concordancia de texto, tomar incidentes relevantes totales si existen
            $conteoGenerico = $incidentesModo === 0;
            $incidentesHistoricos = $incidentesModo > 0 ? $incidentesModo : $incidentesRelevantes->count();

            // FASE 2: Matriz de Inferencia (Reglas FMEA-MSR)
            // Severidad (S) y MSR ya derivados del mapeo fijo (Tablas 20/22) al guardar el catálogo
            $S = $modo->valor_severidad_base ?? 5;
            $MSR = $modo->valor_msr_base ?? 4;

            // Frecuencia (F) Dinámica - Tabla 21
            if ($porcentajeDesgaste >= 80 && $incidentesHistoricos > 2) {
                $F = 9; // Muy Alta
            } elseif ($porcentajeDesgaste >= 40 || $incidentesHistoricos >= 1) {
                $F = 6; // Moderada
            } else {
                $F = 2; // Baja
            }

            // FASE 3: Cálculo Jerárquico del Score (Nivel 2 + Nivel 1)
            $score = $this->calcularScore($S, $F, $MSR, $D);

            // FASE 4: Clasificación y Alerta (El Semáforo)
            $infoClasificacion = $this->obtenerNivelYAlerta($score);

            // Evaluación anterior vigente de este (activo, modo_falla), si existe,
            // para decidir si corresponde generar una alerta nueva (idempotencia).
            $evaluacionAnterior = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                ->where('fk_id_modo_falla', $modo->id_modo_falla)
                ->where('vigente', true)
                ->orderByDesc('fecha_evaluacion')
                ->first();

            // Conservar historial real: la evaluación anterior deja de ser vigente.
            EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                ->where('fk_id_modo_falla', $modo->id_modo_falla)
                ->where('vigente', true)
                ->update(['vigente' => false]);

            $evaluacion = EvaluacionRiesgo::create([
                'fk_id_activo' => $activo->id_activo,
                'fk_id_modo_falla' => $modo->id_modo_falla,
                'valor_severidad' => $S,
                'valor_frecuencia' => $F,
                'valor_msr' => $MSR,
                'score_calculado' => $score,
                'nivel_riesgo' => $infoClasificacion['nivel'],
                'vigente' => true,
                'fecha_evaluacion' => now(),
                'conteo_generico' => $conteoGenerico,
            ]);

            // Alerta idempotente: solo si es la primera evaluación de este par
            // o si el nivel de riesgo cambió respecto a la evaluación anterior.
            if (!$evaluacionAnterior || $evaluacionAnterior->nivel_riesgo !== $infoClasificacion['nivel']) {
                Alerta::create([
                    'fk_id_evaluacion' => $evaluacion->id_evaluacion,
                    'tipo_alerta' => $infoClasificacion['tipo_alerta'],
                    'fecha_generacion' => now(),
                    'notificacion_enviada' => true,
                ]);
            }

            // Sugerencia de tratamiento (Alto/Crítico): borrador automático,
            // solo si no hay ya uno activo para este (activo, modo_falla) —
            // evita duplicar borradores en cada corrida de "Evaluar Todos".
            if (isset(self::MAPA_TRATAMIENTO_SUGERIDO[$infoClasificacion['nivel']])) {
                $tieneTratamientoActivo = PlanTratamientoRiesgo::whereIn('estado_tratamiento', ['Pendiente', 'En Proceso'])
                    ->whereHas('evaluacion', function ($q) use ($activo, $modo) {
                        $q->where('fk_id_activo', $activo->id_activo)
                          ->where('fk_id_modo_falla', $modo->id_modo_falla);
                    })
                    ->exists();

                if (!$tieneTratamientoActivo) {
                    $sugerencia = self::MAPA_TRATAMIENTO_SUGERIDO[$infoClasificacion['nivel']];
                    $tratamientoSugerido = PlanTratamientoRiesgo::create([
                        'fk_id_evaluacion' => $evaluacion->id_evaluacion,
                        'estrategia' => $sugerencia['estrategia'],
                        'accion_preventiva' => $sugerencia['accion_preventiva'],
                        'estado_tratamiento' => 'Pendiente',
                        'origen' => 'automatico',
                        'fecha_compromiso' => now()->addDays($sugerencia['dias_plazo'])->toDateString(),
                    ]);
                    $tratamientoSugerido->setRelation('evaluacion', $evaluacion);
                    $this->crearMantenimientoSiMitiga($tratamientoSugerido);
                }
            }

            $evaluacionesRealizadas[] = [
                'modo_falla' => $modo->nombre_modo_falla,
                'severidad' => $S,
                'frecuencia' => $F,
                'msr' => $MSR,
                'dependencia' => $D,
                'score' => $score,
                'nivel' => $infoClasificacion['nivel'],
                'tipo_alerta' => $infoClasificacion['tipo_alerta'],
            ];
        }

        // FASE 5: Score Global del Activo — calculado al vuelo sobre las
        // evaluaciones vigentes, no sobre un acumulador en memoria.
        $maxScoreVigente = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('vigente', true)
            ->max('score_calculado');
        $maxScore = $maxScoreVigente !== null ? (float) $maxScoreVigente : 1.0;
        $infoGlobal = $this->obtenerNivelYAlerta($maxScore);

        // Cache de solo lectura para las vistas de inventario que aún
        // muestran este campo (ver comentario en el modelo ActivoTecnologico).
        $activo->update([
            'criticidad_institucional' => $infoGlobal['criticidad'],
        ]);

        return [
            'activo' => $activo,
            'antiguedad_meses' => $antiguedadMeses,
            'vida_util_meses' => $vidaUtilMeses,
            'porcentaje_desgaste' => $porcentajeDesgaste,
            'total_incidentes' => $activo->incidentes->count(),
            'max_score' => $maxScore,
            'criticidad_global' => $infoGlobal['criticidad'],
            'alerta_global' => $infoGlobal['tipo_alerta'],
            'evaluaciones' => $evaluacionesRealizadas,
            'sin_modos_falla' => false,
            // Contexto de la selección granular: permite a la vista explicar
            // que los modos no incluidos conservan su evaluación vigente, en
            // vez de dar a entender que quedaron sin evaluar.
            'evaluacion_parcial' => $modosFalla->count() < $modosCategoria->count(),
            'modos_categoria' => $modosCategoria->count(),
            'modos_conservados' => $modosCategoria->count() - $modosFalla->count(),
        ];
    }

    /**
     * Evalúa todos los activos tecnológicos del inventario y devuelve un
     * resumen del lote (para mostrar en pantalla qué hizo realmente el motor).
     */
    public function evaluarTodos(): array
    {
        $activos = ActivoTecnologico::all();

        $detalle = [];
        $conteoPorCriticidad = ['Crítica' => 0, 'Alta' => 0, 'Media' => 0, 'Baja' => 0];
        $sinModosFalla = 0;
        $alertasGeneradas = 0;
        $tratamientosGenerados = 0;

        foreach ($activos as $activo) {
            $alertasAntes = Alerta::count();
            $tratamientosAntes = PlanTratamientoRiesgo::count();

            $resultado = $this->evaluarActivo($activo->id_activo);

            $alertasGeneradas += Alerta::count() - $alertasAntes;
            $tratamientosGenerados += PlanTratamientoRiesgo::count() - $tratamientosAntes;

            if ($resultado['sin_modos_falla']) {
                $sinModosFalla++;
                $detalle[] = [
                    'nombre_equipo' => $activo->nombre_equipo,
                    'codigo_patrimonial' => $activo->codigo_patrimonial,
                    'sin_modos_falla' => true,
                ];
                continue;
            }

            $conteoPorCriticidad[$resultado['criticidad_global']] = ($conteoPorCriticidad[$resultado['criticidad_global']] ?? 0) + 1;

            $detalle[] = [
                'nombre_equipo' => $activo->nombre_equipo,
                'codigo_patrimonial' => $activo->codigo_patrimonial,
                'sin_modos_falla' => false,
                'max_score' => $resultado['max_score'],
                'criticidad' => $resultado['criticidad_global'],
                'alerta' => $resultado['alerta_global'],
            ];
        }

        return [
            'total_activos' => $activos->count(),
            'evaluados' => $activos->count() - $sinModosFalla,
            'sin_modos_falla' => $sinModosFalla,
            'conteo_por_criticidad' => $conteoPorCriticidad,
            'alertas_generadas' => $alertasGeneradas,
            'tratamientos_generados' => $tratamientosGenerados,
            'detalle' => $detalle,
        ];
    }
}
