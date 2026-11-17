<?php

namespace App\Services;

use App\Models\ActivoTecnologico;
use App\Models\AuditoriaInterna;
use App\Models\AuditoriaMuestra;
use App\Models\HallazgoAuditoria;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use Illuminate\Support\Carbon;

/**
 * Lógica de negocio del Informe General de Auditoría Interna de TI. Los
 * controladores solo llaman a este servicio, nunca reimplementan estas
 * reglas (mismo patrón que MotorFmeaService para el motor de riesgo).
 *
 * Regla de diseño transversal: el riesgo del motor FMEA NUNCA es objeto de
 * auditoría; solo prioriza la muestra (generarMuestra) y sugiere gravedad de
 * hallazgos (sugerirClasificacion).
 */
class AuditoriaInternaService
{
    public function generarCodigoAuditoria(int $gestion): string
    {
        $prefijo = config('auditoria.codigo_prefijo');
        $consecutivo = AuditoriaInterna::where('gestion', $gestion)->count() + 1;

        return sprintf('%s-%d-%02d', $prefijo, $gestion, $consecutivo);
    }

    public function generarCodigoHallazgo(AuditoriaInterna $auditoria): string
    {
        $consecutivo = HallazgoAuditoria::where('fk_id_auditoria', $auditoria->id)->count() + 1;

        return sprintf('H-%d-TI-%02d', $auditoria->gestion, $consecutivo);
    }

    /**
     * Incluye el 100% de los activos con evaluación vigente Crítico o Alto, y
     * completa con activos aleatorios hasta llegar a max(mínimo, %configurado
     * del total). Solo permitido en BORRADOR; si ya hay muestra, se borra
     * únicamente la parte NO verificada antes de regenerar (no se pierde
     * trabajo de verificación ya hecho).
     */
    public function generarMuestra(AuditoriaInterna $auditoria): AuditoriaInterna
    {
        if ($auditoria->estado !== 'BORRADOR') {
            throw new \RuntimeException('Solo se puede generar la muestra mientras la auditoría está en BORRADOR.');
        }

        $auditoria->muestra()->whereNull('encontrado')->delete();

        $idsYaEnMuestra = $auditoria->muestra()->pluck('fk_id_activo')->all();

        $activosRiesgo = ActivoTecnologico::whereIn('nivel_riesgo', config('auditoria.muestra.incluir_siempre_niveles'))
            ->whereNotIn('id_activo', $idsYaEnMuestra)
            ->get();

        foreach ($activosRiesgo as $activo) {
            AuditoriaMuestra::create([
                'fk_id_auditoria' => $auditoria->id,
                'fk_id_activo' => $activo->id_activo,
                'nivel_riesgo_al_muestrear' => $activo->nivel_riesgo,
                'motivo_inclusion' => 'RIESGO_ALTO_CRITICO',
            ]);
        }

        $totalActivos = ActivoTecnologico::count();
        $tamanoObjetivo = max(
            (int) config('auditoria.muestra.minimo'),
            (int) ceil($totalActivos * ((int) config('auditoria.muestra.porcentaje') / 100))
        );

        $yaIncluidos = $auditoria->muestra()->pluck('fk_id_activo')->all();
        $faltantes = max(0, $tamanoObjetivo - count($yaIncluidos));

        if ($faltantes > 0) {
            $candidatosAleatorios = ActivoTecnologico::whereNotIn('id_activo', $yaIncluidos)
                ->inRandomOrder()
                ->limit($faltantes)
                ->get();

            foreach ($candidatosAleatorios as $activo) {
                AuditoriaMuestra::create([
                    'fk_id_auditoria' => $auditoria->id,
                    'fk_id_activo' => $activo->id_activo,
                    'nivel_riesgo_al_muestrear' => $activo->nivel_riesgo ?? 'Sin evaluar',
                    'motivo_inclusion' => 'ALEATORIO',
                ]);
            }
        }

        return $auditoria->fresh('muestra');
    }

    /**
     * Cada indicador trae 'valor' (null si no se puede calcular), 'meta',
     * 'cumple' y 'apartado'. Un indicador en null NO cuenta para el semáforo
     * (semaforoPorApartado lo excluye del denominador).
     */
    public function calcularIndicadores(AuditoriaInterna $auditoria): array
    {
        $metas = config('auditoria.metas');
        $indicadores = [];

        $indicadores['exactitud_inventario'] = $this->indicadorExactitudInventario($auditoria, $metas['exactitud_inventario']);
        $indicadores['activos_sin_ubicacion_pct'] = $this->indicadorActivosSinUbicacion($metas['activos_sin_ubicacion_pct']);
        $indicadores['vida_util_vencida_pct'] = $this->indicadorVidaUtilVencida($metas['vida_util_vencida_pct']);
        $indicadores['activos_por_etapa'] = $this->indicadorActivosPorEtapa();
        $indicadores['cumplimiento_mantenimiento'] = $this->indicadorCumplimientoMantenimiento($auditoria, $metas['cumplimiento_mantenimiento']);
        $indicadores['preventivo_pct'] = $this->indicadorPreventivoPct($auditoria, $metas['preventivo_pct']);
        $indicadores['tmr_incidentes_dias'] = $this->indicadorTmrIncidentes($auditoria, $metas['tmr_incidentes_dias']);
        $indicadores['incidentes_abiertos_pct'] = $this->indicadorIncidentesAbiertos($auditoria, $metas['incidentes_abiertos_pct']);
        $indicadores['admins_sistema'] = $this->indicadorAdminsSistema($metas['admins_sistema']);
        $indicadores['hallazgos_anteriores_cerrados'] = $this->indicadorHallazgosAnterioresCerrados($auditoria, $metas['hallazgos_anteriores_cerrados']);

        return $indicadores;
    }

    private function cumpleMeta(?float $valor, array $meta): ?bool
    {
        if (is_null($valor)) {
            return null;
        }

        return $meta['op'] === '>=' ? $valor >= $meta['valor'] : $valor <= $meta['valor'];
    }

    private function indicadorExactitudInventario(AuditoriaInterna $auditoria, array $meta): array
    {
        $verificados = $auditoria->muestra()->get()->filter->estaVerificada();

        if ($verificados->isEmpty()) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.1', 'motivo' => 'La muestra no tiene activos verificados.'];
        }

        $exactos = $verificados->filter(fn ($m) => $m->encontrado && $m->ubicacion_coincide && $m->estado_coincide)->count();
        $valor = round(($exactos / $verificados->count()) * 100, 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.1'];
    }

    private function indicadorActivosSinUbicacion(array $meta): array
    {
        $total = ActivoTecnologico::count();
        if ($total === 0) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.1', 'motivo' => 'No hay activos registrados.'];
        }

        $sinUbicacion = ActivoTecnologico::whereNull('fk_id_ubicacion')->count();
        $valor = round(($sinUbicacion / $total) * 100, 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.1'];
    }

    private function indicadorVidaUtilVencida(array $meta): array
    {
        $activos = ActivoTecnologico::with('categoria')->get();
        if ($activos->isEmpty()) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.2', 'motivo' => 'No hay activos registrados.'];
        }

        $vencidos = $activos->filter(function ($a) {
            $vidaUtilMeses = $a->categoria->vida_util_meses ?? 60;
            $antiguedadMeses = $a->fecha_adquisicion ? Carbon::parse($a->fecha_adquisicion)->diffInMonths(now()) : 0;

            return $antiguedadMeses > $vidaUtilMeses;
        })->count();

        $valor = round(($vencidos / $activos->count()) * 100, 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.2'];
    }

    private function indicadorActivosPorEtapa(): array
    {
        $porEtapa = ActivoTecnologico::selectRaw('etapa_ciclo_vida, COUNT(*) as total')
            ->groupBy('etapa_ciclo_vida')
            ->pluck('total', 'etapa_ciclo_vida');

        return ['valor' => $porEtapa->toArray(), 'meta' => null, 'cumple' => null, 'apartado' => '7.2', 'informativo' => true];
    }

    private function indicadorCumplimientoMantenimiento(AuditoriaInterna $auditoria, array $meta): array
    {
        $query = Mantenimiento::whereBetween('fecha_programada', [$auditoria->fecha_inicio, $auditoria->fecha_fin]);
        $programados = (clone $query)->count();

        if ($programados === 0) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.3', 'motivo' => 'No hay mantenimientos programados en el período.'];
        }

        $ejecutados = (clone $query)->whereNotNull('fecha_ejecucion')->count();
        $valor = round(($ejecutados / $programados) * 100, 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.3'];
    }

    private function indicadorPreventivoPct(AuditoriaInterna $auditoria, array $meta): array
    {
        $query = Mantenimiento::whereBetween('fecha_programada', [$auditoria->fecha_inicio, $auditoria->fecha_fin]);
        $total = (clone $query)->count();

        if ($total === 0) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.3', 'motivo' => 'No hay mantenimientos programados en el período.'];
        }

        $preventivos = (clone $query)->where('tipo_mantenimiento', 'Preventivo')->count();
        $valor = round(($preventivos / $total) * 100, 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.3'];
    }

    private function indicadorTmrIncidentes(AuditoriaInterna $auditoria, array $meta): array
    {
        $cerrados = Incidente::whereBetween('fecha_incidente', [$auditoria->fecha_inicio, $auditoria->fecha_fin])
            ->whereNotNull('fecha_cierre')
            ->get();

        if ($cerrados->isEmpty()) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.4', 'motivo' => 'No hay incidentes cerrados en el período.'];
        }

        $valor = round($cerrados->avg(fn ($i) => Carbon::parse($i->fecha_incidente)->diffInDays(Carbon::parse($i->fecha_cierre))), 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.4'];
    }

    private function indicadorIncidentesAbiertos(AuditoriaInterna $auditoria, array $meta): array
    {
        $incidentes = Incidente::whereBetween('fecha_incidente', [$auditoria->fecha_inicio, $auditoria->fecha_fin])->get();

        if ($incidentes->isEmpty()) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.4', 'motivo' => 'No hay incidentes en el período.'];
        }

        $abiertosACorte = $incidentes->filter(function ($i) use ($auditoria) {
            $cerradoAntes = $i->fecha_cierre && Carbon::parse($i->fecha_cierre)->lte(Carbon::parse($auditoria->fecha_corte));

            return !$cerradoAntes;
        })->count();

        $valor = round(($abiertosACorte / $incidentes->count()) * 100, 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.4'];
    }

    private function indicadorAdminsSistema(array $meta): array
    {
        $total = Usuario::where('fk_id_rol', (int) config('roles.admin'))->count();

        return ['valor' => $total, 'meta' => $meta, 'cumple' => $this->cumpleMeta($total, $meta), 'apartado' => '7.5'];
    }

    private function indicadorHallazgosAnterioresCerrados(AuditoriaInterna $auditoria, array $meta): array
    {
        if (!$auditoria->fk_id_auditoria_anterior) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.6', 'motivo' => 'No aplica: no existe una auditoría anterior.'];
        }

        $hallazgos = HallazgoAuditoria::where('fk_id_auditoria', $auditoria->fk_id_auditoria_anterior)->get();

        if ($hallazgos->isEmpty()) {
            return ['valor' => null, 'meta' => $meta, 'cumple' => null, 'apartado' => '7.6', 'motivo' => 'La auditoría anterior no registró hallazgos.'];
        }

        $cerrados = $hallazgos->where('estado', 'CERRADO')->count();
        $valor = round(($cerrados / $hallazgos->count()) * 100, 1);

        return ['valor' => $valor, 'meta' => $meta, 'cumple' => $this->cumpleMeta($valor, $meta), 'apartado' => '7.6'];
    }

    /**
     * % de indicadores calculables (valor no null) que cumplen su meta, por
     * apartado 7.1..7.6. Rojo si <60% o hay un hallazgo NC_MAYOR en el
     * apartado; ámbar si 60-84% o hay un NC_MENOR; verde si >=85% y sin no
     * conformidades. Umbrales desde config('auditoria.semaforo').
     */
    public function semaforoPorApartado(AuditoriaInterna $auditoria): array
    {
        $indicadores = $this->calcularIndicadores($auditoria);
        $hallazgos = $auditoria->hallazgos;
        $umbrales = config('auditoria.semaforo');
        $resultado = [];

        foreach (HallazgoAuditoria::APARTADOS as $apartado) {
            $delApartado = collect($indicadores)->filter(fn ($i) => $i['apartado'] === $apartado && !($i['informativo'] ?? false));
            $calculables = $delApartado->filter(fn ($i) => !is_null($i['valor']));

            $pctCumplimiento = $calculables->isNotEmpty()
                ? round(($calculables->filter(fn ($i) => $i['cumple'])->count() / $calculables->count()) * 100, 1)
                : null;

            $hallazgosApartado = $hallazgos->where('apartado', $apartado);
            $hayNcMayor = $hallazgosApartado->contains('clasificacion', 'NC_MAYOR');
            $hayNcMenor = $hallazgosApartado->contains('clasificacion', 'NC_MENOR');

            if ($hayNcMayor || ($pctCumplimiento !== null && $pctCumplimiento < $umbrales['amarillo'])) {
                $color = 'ROJO';
            } elseif ($hayNcMenor || $pctCumplimiento === null || $pctCumplimiento < $umbrales['verde']) {
                $color = 'AMARILLO';
            } else {
                $color = 'VERDE';
            }

            $resultado[$apartado] = [
                'color' => $color,
                'porcentaje_cumplimiento' => $pctCumplimiento,
                'indicadores' => $delApartado->toArray(),
            ];
        }

        return $resultado;
    }

    public function opinionGeneral(AuditoriaInterna $auditoria): string
    {
        $semaforo = $this->semaforoPorApartado($auditoria);
        $colores = collect($semaforo)->pluck('color');

        if ($colores->contains('ROJO')) {
            return 'INSATISFACTORIO';
        }
        if ($colores->contains('AMARILLO')) {
            return 'CON_OBSERVACIONES';
        }

        return 'SATISFACTORIO';
    }

    /**
     * Solo sugiere; el auditor decide la clasificación final. Se basa en el
     * nivel de riesgo (motor FMEA) de los activos vinculados al hallazgo —
     * el más severo entre todos los vinculados.
     */
    public function sugerirClasificacion(HallazgoAuditoria $hallazgo): ?string
    {
        $niveles = $hallazgo->activos()->pluck('nivel_riesgo')->filter();

        if ($niveles->contains('Crítico')) {
            return 'NC_MAYOR';
        }
        if ($niveles->contains('Alto')) {
            return 'NC_MENOR';
        }

        return null;
    }

    /**
     * Al crear una auditoría, preselecciona la última APROBADA como
     * fk_id_auditoria_anterior y devuelve sus hallazgos ABIERTO/EN_PROCESO
     * para la pestaña de Seguimiento.
     */
    public function cargarSeguimientoAnterior(AuditoriaInterna $auditoria)
    {
        if (!$auditoria->fk_id_auditoria_anterior) {
            return collect();
        }

        return HallazgoAuditoria::where('fk_id_auditoria', $auditoria->fk_id_auditoria_anterior)
            ->whereIn('estado', ['ABIERTO', 'EN_PROCESO'])
            ->get();
    }

    public function ultimaAuditoriaAprobada(): ?AuditoriaInterna
    {
        return AuditoriaInterna::where('estado', 'APROBADO')->orderByDesc('fecha_aprobacion')->first();
    }

    /**
     * Valida que la auditoría esté completa (objetivo, alcance, conclusiones,
     * muestra 100% verificada, todos los hallazgos con responsable y fecha de
     * compromiso), congela snapshot_indicadores y pasa a APROBADO. Desde ese
     * momento el informe se construye desde el snapshot, no desde datos en
     * vivo — por eso no cambia aunque los datos del sistema cambien después.
     *
     * @throws \RuntimeException si falta algún requisito.
     */
    public function aprobar(AuditoriaInterna $auditoria): AuditoriaInterna
    {
        if (empty($auditoria->objetivo) || empty($auditoria->alcance) || empty($auditoria->conclusiones)) {
            throw new \RuntimeException('No se puede aprobar: faltan objetivo, alcance o conclusiones.');
        }

        if (!$auditoria->fk_id_responsable_ti || !$auditoria->fk_id_director) {
            throw new \RuntimeException('No se puede aprobar: falta asignar Responsable de TI o Dirección para la sección de Aprobación y Distribución.');
        }

        $muestra = $auditoria->muestra;
        if ($muestra->isEmpty() || $muestra->contains(fn ($m) => !$m->estaVerificada())) {
            throw new \RuntimeException('No se puede aprobar: la muestra de verificación física no está completa al 100%.');
        }

        $hallazgoIncompleto = $auditoria->hallazgos->contains(
            fn ($h) => empty($h->responsable) || empty($h->fecha_compromiso)
        );
        if ($hallazgoIncompleto) {
            throw new \RuntimeException('No se puede aprobar: hay hallazgos sin responsable o sin fecha de compromiso.');
        }

        $semaforo = $this->semaforoPorApartado($auditoria);
        $opinion = $this->opinionGeneral($auditoria);

        $auditoria->update([
            'snapshot_indicadores' => [
                'indicadores' => $this->calcularIndicadores($auditoria),
                'semaforo' => $semaforo,
                'opinion' => $opinion,
                'resumen_ejecutivo' => $this->generarResumenEjecutivo($auditoria),
            ],
            'opinion_calculada' => $opinion,
            'estado' => 'APROBADO',
            'fecha_aprobacion' => now(),
        ]);

        \App\Models\Log::create([
            'id_usuario' => auth()->id() ?? (\App\Models\Usuario::first()->id_usuario ?? 1),
            'accion_realizada' => 'Aprobación de Informe General de Auditoría Interna de TI',
            'tabla_afectada' => 'auditorias_internas',
            'registro_id' => $auditoria->id,
            'fecha_hora' => now(),
            'valor_anterior' => null,
            'valor_nuevo' => 'Código: ' . $auditoria->codigo . ' — Opinión: ' . $opinion,
        ]);

        return $auditoria->fresh();
    }

    // =========================================================================
    // Generación de texto mediante PLN (NLG determinística por plantillas)
    // =========================================================================
    //
    // Mismo enfoque que InformesController::generarIntroduccionAuditoria() /
    // generarConclusionAuditoria() del Informe General "consolidado": no se
    // llama a ningún servicio externo de IA. El texto se arma por
    // interpolación de datos que este mismo servicio ya calcula (indicadores,
    // semáforo, opinión, muestra, hallazgos) — nunca inventa causas, cifras
    // ni predicciones, y cambia solo si esos datos cambian. "Generar con PLN"
    // es una acción explícita del auditor que rellena los campos de texto;
    // siguen siendo editables antes de enviar a revisión o aprobar.

    public function generarTextoObjetivo(AuditoriaInterna $auditoria): string
    {
        return sprintf(
            'Evaluar el grado de cumplimiento del Sistema de Gestión de Activos Tecnológicos (SIATEC) frente a los criterios de auditoría aplicables, correspondiente a la gestión %d, con fecha de corte al %s.',
            $auditoria->gestion,
            Carbon::parse($auditoria->fecha_corte)->format('d/m/Y')
        );
    }

    public function generarTextoAlcance(AuditoriaInterna $auditoria): string
    {
        return sprintf(
            'Inventario de activos tecnológicos, mantenimiento, incidentes, clasificación por criticidad y roles y permisos del sistema SIATEC, correspondientes al período comprendido entre el %s y el %s.',
            Carbon::parse($auditoria->fecha_inicio)->format('d/m/Y'),
            Carbon::parse($auditoria->fecha_fin)->format('d/m/Y')
        );
    }

    public function generarTextoAntecedentes(AuditoriaInterna $auditoria): string
    {
        $anterior = $auditoria->fk_id_auditoria_anterior
            ? ($auditoria->relationLoaded('auditoriaAnterior') ? $auditoria->auditoriaAnterior : AuditoriaInterna::find($auditoria->fk_id_auditoria_anterior))
            : null;

        if (!$anterior) {
            return 'No se registran auditorías internas de TI previas en el sistema; la presente constituye la primera auditoría formal registrada.';
        }

        return sprintf(
            'Se registra una auditoría interna de TI previa, %s (gestión %d), con opinión %s. La presente auditoría da seguimiento a sus hallazgos abiertos o en proceso.',
            $anterior->codigo,
            $anterior->gestion,
            $anterior->opinion_calculada ?? 'no registrada'
        );
    }

    public function generarTextoMetodologia(AuditoriaInterna $auditoria): string
    {
        $muestra = $auditoria->muestra;

        if ($muestra->isEmpty()) {
            return 'Aún no se generó la muestra de verificación física; esta sección se completa automáticamente una vez generada.';
        }

        $verificados = $muestra->filter->estaVerificada()->count();

        return sprintf(
            'Se aplicó un muestreo sobre el inventario registrado en el sistema: %d activos seleccionados de un total de %d registrados (%d por presentar nivel de riesgo Alto o Crítico y %d de forma aleatoria), de los cuales %d cuentan con verificación física registrada, conforme a los parámetros de muestreo configurados en el sistema.',
            $muestra->count(),
            ActivoTecnologico::count(),
            $muestra->where('motivo_inclusion', 'RIESGO_ALTO_CRITICO')->count(),
            $muestra->where('motivo_inclusion', 'ALEATORIO')->count(),
            $verificados
        );
    }

    public function generarTextoLimitaciones(AuditoriaInterna $auditoria): string
    {
        $oraciones = [
            'El sistema no registra el responsable individual de cada activo, el tipo de incidente reportado ni la fecha de último acceso de los usuarios; estos aspectos no forman parte de los resultados de esta auditoría.',
            'Los resultados se basan en una muestra del inventario y en la información registrada en el sistema a la fecha de corte, no en una verificación del 100% de los activos.',
        ];

        $noVerificados = $auditoria->muestra->filter(fn ($m) => !$m->estaVerificada())->count();
        if ($noVerificados > 0) {
            $oraciones[] = sprintf('%d activos de la muestra no contaban con verificación física registrada al momento de generar este texto.', $noVerificados);
        }

        return implode(' ', $oraciones);
    }

    public function generarTextoBuenasPracticas(AuditoriaInterna $auditoria): string
    {
        $apartadosVerdes = collect($this->semaforoPorApartado($auditoria))
            ->filter(fn ($info) => $info['color'] === 'VERDE')
            ->keys();

        if ($apartadosVerdes->isEmpty()) {
            return 'No se identifican apartados con cumplimiento sobresaliente (semáforo verde) en el período evaluado.';
        }

        return sprintf(
            'Se identifica un desempeño satisfactorio, con indicadores dentro de la meta establecida, en los apartados %s.',
            $apartadosVerdes->implode(', ')
        );
    }

    /**
     * Párrafo del Resumen Ejecutivo (sección 1 del Informe General): describe
     * de forma breve y objetiva el resultado global, sin repetir literalmente
     * la Conclusión (sección 10) — aquí se presenta como panorama inicial,
     * allá como cierre interpretativo.
     */
    public function generarResumenEjecutivo(AuditoriaInterna $auditoria): string
    {
        $opinion = $this->opinionGeneral($auditoria);
        $totalHallazgos = $auditoria->hallazgos->count();
        $porClasificacion = $auditoria->hallazgos->groupBy('clasificacion')->map->count();

        $desgloseHallazgos = $totalHallazgos > 0
            ? sprintf(
                ' Se identifican %d hallazgos: %d No Conformidad Mayor, %d No Conformidad Menor, %d Observación y %d Oportunidad de Mejora.',
                $totalHallazgos,
                $porClasificacion['NC_MAYOR'] ?? 0,
                $porClasificacion['NC_MENOR'] ?? 0,
                $porClasificacion['OBSERVACION'] ?? 0,
                $porClasificacion['OPORTUNIDAD_MEJORA'] ?? 0
            )
            : ' No se identifican hallazgos en el período evaluado.';

        return sprintf(
            'La presente auditoría interna de TI, gestión %d, evalúa el cumplimiento del Sistema de Gestión de Activos Tecnológicos (SIATEC) con fecha de corte al %s. Sobre la base de los indicadores calculados y la muestra de verificación física, la opinión general resultante es %s.%s',
            $auditoria->gestion,
            Carbon::parse($auditoria->fecha_corte)->format('d/m/Y'),
            $opinion,
            $desgloseHallazgos
        );
    }

    public function generarTextoConclusiones(AuditoriaInterna $auditoria): string
    {
        $indicadores = $this->calcularIndicadores($auditoria);
        $semaforo = $this->semaforoPorApartado($auditoria);
        $porColor = collect($semaforo)->groupBy('color')->map->count();
        $oraciones = [];

        $oraciones[] = sprintf('La opinión general resultante de la presente auditoría es %s.', $this->opinionGeneral($auditoria));
        $oraciones[] = sprintf(
            'De los 6 apartados evaluados, %d se ubican en semáforo verde, %d en ámbar y %d en rojo.',
            $porColor['VERDE'] ?? 0,
            $porColor['AMARILLO'] ?? 0,
            $porColor['ROJO'] ?? 0
        );

        $totalHallazgos = $auditoria->hallazgos->count();
        if ($totalHallazgos > 0) {
            $porClasificacion = $auditoria->hallazgos->groupBy('clasificacion')->map->count();
            $oraciones[] = sprintf(
                'Se registran %d hallazgos: %d No Conformidad Mayor, %d No Conformidad Menor, %d Observación y %d Oportunidad de Mejora.',
                $totalHallazgos,
                $porClasificacion['NC_MAYOR'] ?? 0,
                $porClasificacion['NC_MENOR'] ?? 0,
                $porClasificacion['OBSERVACION'] ?? 0,
                $porClasificacion['OPORTUNIDAD_MEJORA'] ?? 0
            );
        } else {
            $oraciones[] = 'No se registran hallazgos en la presente auditoría.';
        }

        if (($indicadores['exactitud_inventario']['valor'] ?? null) !== null) {
            $oraciones[] = sprintf('La exactitud del inventario verificado físicamente contra el sistema es de %s%%.', number_format($indicadores['exactitud_inventario']['valor'], 1));
        }

        $oraciones[] = 'Los resultados presentados corresponden a los datos y a la muestra verificada registrados en el sistema a la fecha de corte de esta auditoría.';

        return implode(' ', $oraciones);
    }

    /**
     * Regenera los 7 campos narrativos de la auditoría a partir de sus datos
     * actuales. No persiste nada por sí sola — el controlador decide si
     * guarda el resultado (botón "Generar con PLN" en el asistente).
     */
    public function generarTextosAutomaticos(AuditoriaInterna $auditoria): array
    {
        return [
            'objetivo' => $this->generarTextoObjetivo($auditoria),
            'alcance' => $this->generarTextoAlcance($auditoria),
            'antecedentes' => $this->generarTextoAntecedentes($auditoria),
            'metodologia' => $this->generarTextoMetodologia($auditoria),
            'limitaciones' => $this->generarTextoLimitaciones($auditoria),
            'buenas_practicas' => $this->generarTextoBuenasPracticas($auditoria),
            'conclusiones' => $this->generarTextoConclusiones($auditoria),
        ];
    }
}
