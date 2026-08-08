<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanTratamientoRiesgo extends Model
{
    use HasFactory;

    protected $table = 'plan_tratamiento_riesgo';
    protected $primaryKey = 'id_tratamiento';

    protected $fillable = [
        'fk_id_evaluacion',
        'estrategia',
        'accion_preventiva',
        'estado_tratamiento',
        'fecha_compromiso',
        'origen',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(EvaluacionRiesgo::class, 'fk_id_evaluacion', 'id_evaluacion');
    }
}
