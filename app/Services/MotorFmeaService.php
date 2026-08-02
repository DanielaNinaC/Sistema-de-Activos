<?php

namespace App\Services;

use App\Models\ActivoTecnologico;
use App\Models\Alerta;
use App\Models\EvaluacionRiesgo;
use App\Models\ModoFalla;
use App\Models\PesoAhp;
use Carbon\Carbon;

class MotorFmeaService
{
    /**
     * Nivel de riesgo según el Score calculado (Tabla 27 de la Tesis ISO 31000 / FMEA-MSR)
     */
    public function obtenerNivelYAlerta(float $score): array
    {
        if ($score >= 8.1) {
            return [
                'nivel' => 'Crítico',
                'tipo_alerta' => 'Roja',
                'criticidad' => 'Crítica',
                'color_hex' => '#ef4444',
            ];
        }

        if ($score >= 6.1) {
            return [
                'nivel' => 'Alto',
                'tipo_alerta' => 'Naranja',
                'criticidad' => 'Alta',
                'color_hex' => '#f97316',
            ];
        }

        if ($score >= 3.1) {
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
     * Calcula la antigüedad en meses a partir de la fecha de adquisición.
     */
    public function calcularAntiguedadMeses(?string $fechaAdquisicion): int
    {
        if (!$fechaAdquisicion) {
            return 12; // Por defecto 1 año si no hay registro
        }

        try {
            if (strlen($fechaAdquisicion) === 4 && is_numeric($fechaAdquisicion)) {
                $fecha = Carbon::createFromDate((int)$fechaAdquisicion, 1, 1);
            } else {
                $fecha = Carbon::parse($fechaAdquisicion);
            }
            return max(1, (int) $fecha->diffInMonths(now()));
        } catch (\Exception $e) {
            return 12;
        }
    }

    /**
     * Ejecuta el motor de inferencia automatizado para un activo específico.
     */
    public function evaluarActivo(int $idActivo): array
    {
        $activo = ActivoTecnologico::with(['categoria.modosFalla', 'incidentes'])->findOrFail($idActivo);

        // FASE 1: Lectura de Datos del Inventario
        $antiguedadMeses = $this->calcularAntiguedadMeses($activo->fecha_adquisicion);
        $vidaUtilMeses = max(1, $activo->categoria->vida_util_meses ?? 60);
        $porcentajeDesgaste = min(100, round(($antiguedadMeses / $vidaUtilMeses) * 100, 2));

        // Obtener modos de falla de la categoría del activo
        $modosFalla = $activo->categoria->modosFalla ?? collect();
        if ($modosFalla->isEmpty()) {
            // Si la categoría no tiene modos de falla específicos, obtener todos los modos
            $modosFalla = ModoFalla::all();
        }

        // Obtener pesos AHP vigentes
        $pesoObj = PesoAhp::orderByDesc('id_config')->first();
        $w1 = (float) ($pesoObj->peso_severidad ?? 0.648);
        $w2 = (float) ($pesoObj->peso_frecuencia ?? 0.230);
        $w3 = (float) ($pesoObj->peso_msr ?? 0.122);

        $evaluacionesRealizadas = [];
        $scoresCalculados = [];

        foreach ($modosFalla as $modo) {
            // Contar incidentes del activo para este modo de falla o totales
            $incidentesModo = $activo->incidentes
                ->filter(function ($inc) use ($modo) {
                    return str_contains(strtolower($inc->descripcion_falla), strtolower($modo->nombre_modo_falla));
                })->count();

            // Si no hay por concordancia de texto, tomar incidentes totales si existen
            $incidentesHistoricos = $incidentesModo > 0 ? $incidentesModo : $activo->incidentes->count();

            // FASE 2: Matriz de Inferencia (Reglas FMEA-MSR)
            // Severidad (S) - Tabla 20
            $S = $modo->valor_severidad_base ?? 5;

            // Monitoreo (MSR) - Tabla 22
            $MSR = $modo->valor_msr_base ?? 4;

            // Frecuencia (F) Dinámica - Tabla 21
            if ($porcentajeDesgaste >= 80 && $incidentesHistoricos > 2) {
                $F = 9; // Muy Alta
            } elseif ($porcentajeDesgaste >= 40 || $incidentesHistoricos >= 1) {
                $F = 6; // Moderada
            } else {
                $F = 2; // Baja
            }

            // FASE 3: Cálculo Matemático (Ecuación 3 AHP)
            $score = round(($S * $w1) + ($F * $w2) + ($MSR * $w3), 2);
            $scoresCalculados[] = $score;

            // FASE 4: Clasificación y Alerta (El Semáforo)
            $infoClasificacion = $this->obtenerNivelYAlerta($score);

            // Guardar o actualizar Evaluación de Riesgo
            $evaluacion = EvaluacionRiesgo::updateOrCreate(
                [
                    'fk_id_activo' => $activo->id_activo,
                    'fk_id_modo_falla' => $modo->id_modo_falla,
                ],
                [
                    'valor_severidad' => $S,
                    'valor_frecuencia' => $F,
                    'valor_msr' => $MSR,
                    'score_calculado' => $score,
                    'nivel_riesgo' => $infoClasificacion['nivel'],
                    'fecha_evaluacion' => now(),
                ]
            );

            // Registrar Alerta automática
            Alerta::create([
                'fk_id_evaluacion' => $evaluacion->id_evaluacion,
                'tipo_alerta' => $infoClasificacion['tipo_alerta'],
                'fecha_generacion' => now(),
                'notificacion_enviada' => true,
            ]);

            $evaluacionesRealizadas[] = [
                'modo_falla' => $modo->nombre_modo_falla,
                'severidad' => $S,
                'frecuencia' => $F,
                'msr' => $MSR,
                'score' => $score,
                'nivel' => $infoClasificacion['nivel'],
                'tipo_alerta' => $infoClasificacion['tipo_alerta'],
            ];
        }

        // FASE 5: Score Global del Activo (MAX Score)
        $maxScore = !empty($scoresCalculados) ? max($scoresCalculados) : 1.0;
        $infoGlobal = $this->obtenerNivelYAlerta($maxScore);

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
        ];
    }

    /**
     * Evalúa todos los activos tecnológicos del inventario.
     */
    public function evaluarTodos(): int
    {
        $activos = ActivoTecnologico::all();
        $total = 0;

        foreach ($activos as $activo) {
            $this->evaluarActivo($activo->id_activo);
            $total++;
        }

        return $total;
    }
}
