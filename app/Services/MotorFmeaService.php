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
use App\Support\Catalogo;
use Carbon\Carbon;

/**
 * Los parámetros del modelo (Tablas 8, 20, 21, 22 y 29 de la tesis: valores de
 * severidad, MSR, dependencia, reglas de frecuencia, umbrales de nivel de
 * riesgo y tratamiento sugerido) viven en la base de datos y se leen mediante
 * App\Support\Catalogo; este servicio solo aplica las fórmulas.
 */
class MotorFmeaService
{
    /**
     * Nivel de riesgo según el Score calculado (Tabla 29 de la Tesis ISO 31000 / FMEA-MSR)
     */
    public function obtenerNivelYAlerta(float $score): array
    {
        return Catalogo::nivelPorScore($score);
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
     * Cálculo del Subscore Técnico de Nivel 2 (FMEA-MSR ponderado por AHP):
     *   SubscoreTecnico = (S * w_severidad) + (F * w_frecuencia) + (MSR * w_msr)
     * Pesos de la tesis: w_severidad = 0.648, w_frecuencia = 0.230, w_msr = 0.122.
     */
    public function calcularSubscoreTecnico(int $S, int $F, int $MSR): float
    {
        $pesos = PesoAhp::where('vigente', true)->first();

        $wS = (float) ($pesos->peso_severidad ?? 0.648);
        $wF = (float) ($pesos->peso_frecuencia ?? 0.230);
        $wMSR = (float) ($pesos->peso_msr ?? 0.122);

        return round(($S * $wS) + ($F * $wF) + ($MSR * $wMSR), 3);
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

        $p1 = (float) ($pesos->peso_riesgo_tecnico ?? 0.750);
        $p2 = (float) ($pesos->peso_dependencia ?? 0.250);

        $subscoreTecnico = $this->calcularSubscoreTecnico($S, $F, $MSR);

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
        if (!Catalogo::estrategiaGeneraMantenimiento($tratamiento->estrategia) || !$tratamiento->evaluacion) {
            return;
        }

        $yaExiste = Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)
            ->whereIn('estado_mantenimiento', Catalogo::estadosMantenimientoAbiertos())
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
            ->whereNotIn('estado_incidente', Catalogo::estadosIncidenteCerrados())
            ->exists();

        Mantenimiento::create([
            'fk_id_activo' => $idActivo,
            'fk_id_usuario_asignado' => $idUsuario,
            'fk_id_origen' => Catalogo::origenMantenimientoId('Evaluación de riesgos'),
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
        $D = Catalogo::valorDependencia($nivelDependencia) ?? Catalogo::valorDependencia('Media') ?? 5;

        // Cierre del ciclo: si ya hubo un mantenimiento ejecutado, solo
        // cuentan como "recurrencia" los incidentes posteriores a esa
        // intervención. Sin mantenimiento previo, se usa todo el historial.
        $fechaMantenimiento = $activo->fechaUltimoMantenimientoCompletado();
        $incidentesRelevantes = $fechaMantenimiento
            ? $activo->incidentes->filter(fn ($inc) => Carbon::parse($inc->fecha_incidente)->greaterThan($fechaMantenimiento))
            : $activo->incidentes;

        $evaluacionesRealizadas = [];
        $pesos = PesoAhp::where('vigente', true)->first();
        $wS = (float) ($pesos->peso_severidad ?? 0.648);
        $wF = (float) ($pesos->peso_frecuencia ?? 0.230);
        $wMSR = (float) ($pesos->peso_msr ?? 0.122);
        $p1 = (float) ($pesos->peso_riesgo_tecnico ?? 0.750);
        $p2 = (float) ($pesos->peso_dependencia ?? 0.250);

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

            // Frecuencia (F) Dinámica - Tabla 21 (reglas en niveles_frecuencia)
            $F = Catalogo::frecuencia($porcentajeDesgaste, $incidentesHistoricos);

            // FASE 3 & 4: Subscore Técnico AHP e Integración Institucional
            $subscoreTecnico = $this->calcularSubscoreTecnico($S, $F, $MSR);
            $score = $this->calcularScore($S, $F, $MSR, $D);
            $indicePreliminar = $F * $S;

            // FASE 5: Clasificación y Alerta (El Semáforo)
            $infoClasificacion = $this->obtenerNivelYAlerta($score);
            $infoS = Catalogo::infoSeveridad($S);
            $infoF = Catalogo::infoFrecuencia($porcentajeDesgaste, $incidentesHistoricos);
            $infoMsr = Catalogo::infoMsr($MSR);

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

            $fechaAhora = now();
            $evaluacion = EvaluacionRiesgo::create([
                'fk_id_activo' => $activo->id_activo,
                'fk_id_modo_falla' => $modo->id_modo_falla,
                'valor_severidad' => $S,
                'valor_frecuencia' => $F,
                'valor_msr' => $MSR,
                'score_calculado' => $score,
                'nivel_riesgo' => $infoClasificacion['nivel'],
                'vigente' => true,
                'fecha_evaluacion' => $fechaAhora,
                'conteo_generico' => $conteoGenerico,
            ]);

            // Alerta idempotente: solo si es la primera evaluación de este par
            // o si el nivel de riesgo cambió respecto a la evaluación anterior.
            if (!$evaluacionAnterior || $evaluacionAnterior->nivel_riesgo !== $infoClasificacion['nivel']) {
                Alerta::create([
                    'fk_id_evaluacion' => $evaluacion->id_evaluacion,
                    'tipo_alerta' => $infoClasificacion['tipo_alerta'],
                    'fecha_generacion' => $fechaAhora,
                    'notificacion_enviada' => true,
                ]);
            }

            // Sugerencia de tratamiento (Alto/Crítico): borrador automático,
            // solo si no hay ya uno activo para este (activo, modo_falla) —
            // evita duplicar borradores en cada corrida de "Evaluar Todos".
            $sugerencia = Catalogo::tratamientoSugerido($infoClasificacion['nivel']);
            if ($sugerencia !== null) {
                $tieneTratamientoActivo = PlanTratamientoRiesgo::whereIn('estado_tratamiento', Catalogo::estadosTratamientoAbiertos())
                    ->whereHas('evaluacion', function ($q) use ($activo, $modo) {
                        $q->where('fk_id_activo', $activo->id_activo)
                          ->where('fk_id_modo_falla', $modo->id_modo_falla);
                    })
                    ->exists();

                if (!$tieneTratamientoActivo) {
                    $tratamientoSugerido = PlanTratamientoRiesgo::create([
                        'fk_id_evaluacion' => $evaluacion->id_evaluacion,
                        'estrategia' => $sugerencia['estrategia'],
                        'estrategia_sugerida' => $sugerencia['estrategia'],
                        'accion_preventiva' => $sugerencia['accion_preventiva'],
                        'accion_sugerida' => $sugerencia['accion_preventiva'],
                        'indicador_seguimiento' => $modo->indicador_seguimiento,
                        'estado_tratamiento' => 'Pendiente',
                        'origen' => 'automatico',
                        'fecha_compromiso' => now()->addDays($sugerencia['dias_plazo'])->toDateString(),
                    ]);
                    $tratamientoSugerido->setRelation('evaluacion', $evaluacion);
                    $this->crearMantenimientoSiMitiga($tratamientoSugerido);
                }
            }

            $evaluacionesRealizadas[] = [
                'id_modo_falla' => $modo->id_modo_falla,
                'modo_falla' => $modo->nombre_modo_falla,
                'severidad' => $S,
                'frecuencia' => $F,
                'msr' => $MSR,
                'dependencia' => $D,
                'indice_preliminar' => $indicePreliminar,
                'subscore_tecnico' => $subscoreTecnico,
                'score' => $score,
                'nivel' => $infoClasificacion['nivel'],
                'tipo_alerta' => $infoClasificacion['tipo_alerta'],
                'color_hex' => $infoClasificacion['color_hex'],
                'recalculado' => true,
                'fecha_evaluacion' => $fechaAhora->format('d/m/Y H:i'),
                'etiqueta_severidad' => $infoS['etiqueta'],
                'criterio_severidad' => $modo->justificacion_severidad ?: $infoS['descripcion'],
                'etiqueta_frecuencia' => $infoF['etiqueta'],
                'criterio_frecuencia' => $infoF['descripcion'],
                'regla_frecuencia' => $infoF['regla'],
                'etiqueta_msr' => $infoMsr['etiqueta'],
                'criterio_msr' => $modo->justificacion_msr ?: $infoMsr['descripcion'],
                'estrategia' => $sugerencia['estrategia'] ?? null,
                'accion_preventiva' => $sugerencia['accion_preventiva'] ?? null,
            ];
        }

        // Incorporar modos de falla conservados (no recalculados en esta corrida)
        // para dar visibilidad completa al estado de riesgo del activo.
        $idsEvaluados = $modosFalla->pluck('id_modo_falla')->all();
        $modosConservados = $modosCategoria->whereNotIn('id_modo_falla', $idsEvaluados);

        foreach ($modosConservados as $modoCons) {
            $evaluacionVigente = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                ->where('fk_id_modo_falla', $modoCons->id_modo_falla)
                ->where('vigente', true)
                ->orderByDesc('fecha_evaluacion')
                ->first();

            if ($evaluacionVigente) {
                $sCons = (int) $evaluacionVigente->valor_severidad;
                $fCons = (int) $evaluacionVigente->valor_frecuencia;
                $msrCons = (int) $evaluacionVigente->valor_msr;
                $subCons = $this->calcularSubscoreTecnico($sCons, $fCons, $msrCons);
                $scoreCons = (float) $evaluacionVigente->score_calculado;
                $indCons = $fCons * $sCons;
                $infoCons = $this->obtenerNivelYAlerta($scoreCons);
                $infoSCons = Catalogo::infoSeveridad($sCons);
                $infoFCons = Catalogo::infoFrecuencia($porcentajeDesgaste, $incidentesRelevantes->count());
                $infoMsrCons = Catalogo::infoMsr($msrCons);
                $sugCons = Catalogo::tratamientoSugerido($evaluacionVigente->nivel_riesgo);

                $evaluacionesRealizadas[] = [
                    'id_modo_falla' => $modoCons->id_modo_falla,
                    'modo_falla' => $modoCons->nombre_modo_falla,
                    'severidad' => $sCons,
                    'frecuencia' => $fCons,
                    'msr' => $msrCons,
                    'dependencia' => $D,
                    'indice_preliminar' => $indCons,
                    'subscore_tecnico' => $subCons,
                    'score' => $scoreCons,
                    'nivel' => $evaluacionVigente->nivel_riesgo,
                    'tipo_alerta' => $infoCons['tipo_alerta'],
                    'color_hex' => $infoCons['color_hex'],
                    'recalculado' => false,
                    'fecha_evaluacion' => Carbon::parse($evaluacionVigente->fecha_evaluacion)->format('d/m/Y H:i'),
                    'etiqueta_severidad' => $infoSCons['etiqueta'],
                    'criterio_severidad' => $modoCons->justificacion_severidad ?: $infoSCons['descripcion'],
                    'etiqueta_frecuencia' => $infoFCons['etiqueta'],
                    'criterio_frecuencia' => $infoFCons['descripcion'],
                    'regla_frecuencia' => $infoFCons['regla'],
                    'etiqueta_msr' => $infoMsrCons['etiqueta'],
                    'criterio_msr' => $modoCons->justificacion_msr ?: $infoMsrCons['descripcion'],
                    'estrategia' => $sugCons['estrategia'] ?? null,
                    'accion_preventiva' => $sugCons['accion_preventiva'] ?? null,
                ];
            }
        }

        // FASE 5: Score Global del Activo — calculado al vuelo sobre las
        // evaluaciones vigentes, no sobre un acumulador en memoria.
        $maxScoreVigente = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('vigente', true)
            ->max('score_calculado');
        $maxScore = $maxScoreVigente !== null ? (float) $maxScoreVigente : 1.0;
        $infoGlobal = $this->obtenerNivelYAlerta($maxScore);

        // Actualizar nivel_riesgo del activo según la regla de evaluaciones vigentes:
        // si tiene 4 o más riesgos vigentes se promedia, sino el último nivel de riesgo.
        $nivelFinal = $this->actualizarNivelRiesgoActivo($activo);

        return [
            'activo' => $activo,
            'antiguedad_meses' => $antiguedadMeses,
            'vida_util_meses' => $vidaUtilMeses,
            'porcentaje_desgaste' => $porcentajeDesgaste,
            'total_incidentes' => $activo->incidentes->count(),
            'incidentes_relevantes' => $incidentesRelevantes->count(),
            'fecha_ultimo_mantenimiento' => $fechaMantenimiento ? $fechaMantenimiento->format('d/m/Y') : null,
            'dependencia_nivel' => $nivelDependencia,
            'dependencia_valor' => $D,
            'pesos_ahp' => [
                'peso_severidad' => $wS,
                'peso_frecuencia' => $wF,
                'peso_msr' => $wMSR,
                'peso_riesgo_tecnico' => $p1,
                'peso_dependencia' => $p2,
            ],
            'max_score' => $maxScore,
            'criticidad_global' => $nivelFinal ?? $infoGlobal['criticidad'],
            'nivel_riesgo' => $nivelFinal,
            'alerta_global' => $infoGlobal['tipo_alerta'],
            'color_global' => $infoGlobal['color_hex'],
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
     * Obtiene el desglose completo de inferencia FMEA-MSR para un activo
     * a partir de sus evaluaciones vigentes registradas, sin recalcular
     * ni alterar la base de datos (para consulta directa en pantalla).
     */
    public function obtenerInferenciaActivo(int $idActivo): ?array
    {
        $activo = ActivoTecnologico::with(['categoria.modosFalla', 'incidentes', 'mantenimientos'])->find($idActivo);
        if (!$activo || !$activo->categoria) {
            return null;
        }

        $antiguedadMeses = $this->calcularAntiguedadMesesActivo($activo);
        $vidaUtilMeses = max(1, $activo->categoria->vida_util_meses ?? 60);
        $porcentajeDesgaste = min(100, round(($antiguedadMeses / $vidaUtilMeses) * 100, 2));

        $modosCategoria = $activo->categoria->modosFalla ?? collect();
        if ($modosCategoria->isEmpty()) {
            return [
                'activo' => $activo,
                'antiguedad_meses' => $antiguedadMeses,
                'vida_util_meses' => $vidaUtilMeses,
                'porcentaje_desgaste' => $porcentajeDesgaste,
                'total_incidentes' => $activo->incidentes->count(),
                'incidentes_relevantes' => 0,
                'fecha_ultimo_mantenimiento' => null,
                'dependencia_nivel' => $activo->categoria->nivel_dependencia ?? 'Media',
                'dependencia_valor' => 5,
                'pesos_ahp' => [
                    'peso_severidad' => 0.648,
                    'peso_frecuencia' => 0.230,
                    'peso_msr' => 0.122,
                    'peso_riesgo_tecnico' => 0.750,
                    'peso_dependencia' => 0.250,
                ],
                'max_score' => null,
                'criticidad_global' => null,
                'nivel_riesgo' => null,
                'alerta_global' => null,
                'color_global' => '#94a3b8',
                'evaluaciones' => [],
                'sin_modos_falla' => true,
                'evaluacion_parcial' => false,
                'modos_categoria' => 0,
                'modos_conservados' => 0,
            ];
        }

        $nivelDependencia = $activo->categoria->nivel_dependencia ?? 'Media';
        $D = Catalogo::valorDependencia($nivelDependencia) ?? Catalogo::valorDependencia('Media') ?? 5;
        $fechaMantenimiento = $activo->fechaUltimoMantenimientoCompletado();
        $incidentesRelevantes = $fechaMantenimiento
            ? $activo->incidentes->filter(fn ($inc) => Carbon::parse($inc->fecha_incidente)->greaterThan($fechaMantenimiento))
            : $activo->incidentes;

        $pesos = PesoAhp::where('vigente', true)->first();
        $wS = (float) ($pesos->peso_severidad ?? 0.648);
        $wF = (float) ($pesos->peso_frecuencia ?? 0.230);
        $wMSR = (float) ($pesos->peso_msr ?? 0.122);
        $p1 = (float) ($pesos->peso_riesgo_tecnico ?? 0.750);
        $p2 = (float) ($pesos->peso_dependencia ?? 0.250);

        $evaluacionesRealizadas = [];

        foreach ($modosCategoria as $modo) {
            $evalVigente = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                ->where('fk_id_modo_falla', $modo->id_modo_falla)
                ->where('vigente', true)
                ->orderByDesc('fecha_evaluacion')
                ->first();

            if ($evalVigente) {
                $S = (int) $evalVigente->valor_severidad;
                $F = (int) $evalVigente->valor_frecuencia;
                $MSR = (int) $evalVigente->valor_msr;
                $subscoreTecnico = $this->calcularSubscoreTecnico($S, $F, $MSR);
                $score = (float) $evalVigente->score_calculado;
                $fechaEv = Carbon::parse($evalVigente->fecha_evaluacion)->format('d/m/Y H:i');
                $nivel = $evalVigente->nivel_riesgo;
            } else {
                $S = (int) ($modo->valor_severidad_base ?? 5);
                $F = Catalogo::frecuencia($porcentajeDesgaste, $incidentesRelevantes->count());
                $MSR = (int) ($modo->valor_msr_base ?? 4);
                $subscoreTecnico = $this->calcularSubscoreTecnico($S, $F, $MSR);
                $score = $this->calcularScore($S, $F, $MSR, $D);
                $fechaEv = 'Sin registrar';
                $nivel = Catalogo::nivelPorScore($score)['nivel'];
            }

            $infoClasificacion = $this->obtenerNivelYAlerta($score);
            $infoS = Catalogo::infoSeveridad($S);
            $infoF = Catalogo::infoFrecuencia($porcentajeDesgaste, $incidentesRelevantes->count());
            $infoMsr = Catalogo::infoMsr($MSR);
            $sugerencia = Catalogo::tratamientoSugerido($nivel);

            $evaluacionesRealizadas[] = [
                'id_modo_falla' => $modo->id_modo_falla,
                'modo_falla' => $modo->nombre_modo_falla,
                'severidad' => $S,
                'frecuencia' => $F,
                'msr' => $MSR,
                'dependencia' => $D,
                'indice_preliminar' => $F * $S,
                'subscore_tecnico' => $subscoreTecnico,
                'score' => $score,
                'nivel' => $nivel,
                'tipo_alerta' => $infoClasificacion['tipo_alerta'],
                'color_hex' => $infoClasificacion['color_hex'],
                'recalculado' => false,
                'fecha_evaluacion' => $fechaEv,
                'etiqueta_severidad' => $infoS['etiqueta'],
                'criterio_severidad' => $modo->justificacion_severidad ?: $infoS['descripcion'],
                'etiqueta_frecuencia' => $infoF['etiqueta'],
                'criterio_frecuencia' => $infoF['descripcion'],
                'regla_frecuencia' => $infoF['regla'],
                'etiqueta_msr' => $infoMsr['etiqueta'],
                'criterio_msr' => $modo->justificacion_msr ?: $infoMsr['descripcion'],
                'estrategia' => $sugerencia['estrategia'] ?? null,
                'accion_preventiva' => $sugerencia['accion_preventiva'] ?? null,
            ];
        }

        $maxScoreVigente = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('vigente', true)
            ->max('score_calculado');
        $maxScore = $maxScoreVigente !== null ? (float) $maxScoreVigente : ($evaluacionesRealizadas ? max(array_column($evaluacionesRealizadas, 'score')) : 1.0);
        $infoGlobal = $this->obtenerNivelYAlerta($maxScore);

        return [
            'activo' => $activo,
            'antiguedad_meses' => $antiguedadMeses,
            'vida_util_meses' => $vidaUtilMeses,
            'porcentaje_desgaste' => $porcentajeDesgaste,
            'total_incidentes' => $activo->incidentes->count(),
            'incidentes_relevantes' => $incidentesRelevantes->count(),
            'fecha_ultimo_mantenimiento' => $fechaMantenimiento ? $fechaMantenimiento->format('d/m/Y') : null,
            'dependencia_nivel' => $nivelDependencia,
            'dependencia_valor' => $D,
            'pesos_ahp' => [
                'peso_severidad' => $wS,
                'peso_frecuencia' => $wF,
                'peso_msr' => $wMSR,
                'peso_riesgo_tecnico' => $p1,
                'peso_dependencia' => $p2,
            ],
            'max_score' => $maxScore,
            'criticidad_global' => $activo->nivel_riesgo ?? $infoGlobal['criticidad'],
            'nivel_riesgo' => $activo->nivel_riesgo ?? $infoGlobal['nivel'],
            'alerta_global' => $infoGlobal['tipo_alerta'],
            'color_global' => $infoGlobal['color_hex'],
            'evaluaciones' => $evaluacionesRealizadas,
            'sin_modos_falla' => false,
            'evaluacion_parcial' => false,
            'modos_categoria' => $modosCategoria->count(),
            'modos_conservados' => 0,
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
        // De la criticidad más alta a la más baja (el catálogo está en orden ascendente).
        $conteoPorCriticidad = array_fill_keys(array_reverse(Catalogo::nombres('niveles_riesgo', 'criticidad')), 0);
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

    /**
     * Actualiza el campo nivel_riesgo del activo según sus evaluaciones FMEA vigentes.
     * Si tiene 4 o más riesgos vigentes, se promedia su score y se asigna el nivel resultante.
     * Si tiene menos de 4 (pero al menos 1), toma el nivel de riesgo de la última evaluación vigente.
     * Si no tiene evaluaciones vigentes, queda en null.
     */
    public function actualizarNivelRiesgoActivo(ActivoTecnologico|int $activo): ?string
    {
        if (is_int($activo)) {
            $activo = ActivoTecnologico::findOrFail($activo);
        }

        $evaluacionesVigentes = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('vigente', true)
            ->orderByDesc('fecha_evaluacion')
            ->orderByDesc('id_evaluacion')
            ->get();

        $conteo = $evaluacionesVigentes->count();

        if ($conteo >= 4) {
            $scorePromedio = (float) $evaluacionesVigentes->avg('score_calculado');
            $infoPromedio = $this->obtenerNivelYAlerta($scorePromedio);
            $nivelFinal = $infoPromedio['nivel'];
        } elseif ($conteo > 0) {
            $nivelFinal = $evaluacionesVigentes->first()->nivel_riesgo;
        } else {
            $nivelFinal = null;
        }

        $activo->update(['nivel_riesgo' => $nivelFinal]);

        return $nivelFinal;
    }
}
