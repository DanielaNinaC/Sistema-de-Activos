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
        'fecha_evaluacion',
    ];

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }

    public function modoFalla()
    {
        return $this->belongsTo(ModoFalla::class, 'fk_id_modo_falla', 'id_modo_falla');
    }
}
