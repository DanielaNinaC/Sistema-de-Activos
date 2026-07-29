<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';
    protected $primaryKey = 'id_mantenimiento';

    protected $fillable = [
        'fk_id_activo',
        'fk_id_usuario_asignado',
        'fk_id_origen',
        'id_evaluacion_origen',
        'fk_id_incidente',
        'tipo_mantenimiento',
        'fecha_programada',
        'fecha_ejecucion',
        'descripcion_trabajo',
        'estado_mantenimiento',
        'evidencia_pdf',
    ];

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }

    public function usuarioAsignado()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_usuario_asignado', 'id_usuario');
    }

    public function origenMantenimiento()
    {
        return $this->belongsTo(OrigenMantenimiento::class, 'fk_id_origen', 'id_origen');
    }

    public function evaluacionOrigen()
    {
        return $this->belongsTo(EvaluacionRiesgo::class, 'id_evaluacion_origen', 'id_evaluacion');
    }

    public function incidente()
    {
        return $this->belongsTo(Incidente::class, 'fk_id_incidente', 'id_incidente');
    }
}
