<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionRiesgo extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_riesgo';
    protected $primaryKey = 'id_evaluacion';

    protected $fillable = [
        'fk_id_activo',
        'fk_id_modo_falla',
        'valor_severidad',
        'valor_frecuencia',
        'valor_msr',
        'score_calculado',
        'nivel_riesgo',
        'vigente',
        'fecha_evaluacion',
        'conteo_generico',
    ];

    protected $casts = [
        'vigente' => 'boolean',
        'conteo_generico' => 'boolean',
    ];

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }

    public function modoFalla()
    {
        return $this->belongsTo(ModoFalla::class, 'fk_id_modo_falla', 'id_modo_falla');
    }

    public function planesTratamiento()
    {
        return $this->hasMany(PlanTratamientoRiesgo::class, 'fk_id_evaluacion', 'id_evaluacion');
    }

    /**
     * Evaluaciones vigentes en Alto/Crítico sin ningún plan de tratamiento
     * asociado (ni Pendiente, En Proceso ni Completado). Única fuente de
     * verdad reutilizada por el módulo de Plan de Tratamiento y por el
     * módulo de Informes — no duplicar esta consulta en otro lugar.
     */
    public function scopeSinTratamiento($query)
    {
        return $query->where('vigente', true)
            ->whereIn('nivel_riesgo', ['Alto', 'Crítico'])
            ->whereDoesntHave('planesTratamiento', function ($q) {
                $q->whereIn('estado_tratamiento', ['Pendiente', 'En Proceso', 'Completado']);
            });
    }
}
