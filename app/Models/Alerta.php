<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    use HasFactory;

    protected $table = 'alertas';
    protected $primaryKey = 'id_alerta';

    protected $fillable = [
        'fk_id_evaluacion',
        'tipo_alerta',
        'fecha_generacion',
        'notificacion_enviada',
    ];

    protected $casts = [
        'notificacion_enviada' => 'boolean',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(EvaluacionRiesgo::class, 'fk_id_evaluacion', 'id_evaluacion');
    }
}
