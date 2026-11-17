<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';
    protected $primaryKey = 'id_mantenimiento';

    public const TAREAS_CHECKLIST = [
        1 => 'Inspección visual del estado físico',
        2 => 'Limpieza externa del equipo',
        3 => 'Limpieza interna de componentes, cuando corresponda',
        4 => 'Verificación de conexiones, cables y conectores',
        5 => 'Verificación de alimentación eléctrica',
        6 => 'Comprobación de encendido y apagado',
        7 => 'Prueba general de funcionamiento',
        8 => 'Identificación de daños, desgaste o anomalías',
        9 => 'Prueba final de funcionamiento',
    ];

    protected $fillable = [
        'fk_id_activo',
        'fk_id_usuario_asignado',
        'fk_id_usuario_finaliza',
        'fk_id_origen',
        'id_evaluacion_origen',
        'fk_id_incidente',
        'tipo_mantenimiento',
        'fecha_programada',
        'fecha_ejecucion',
        'fecha_hora_realizacion',
        'descripcion_trabajo',
        'estado_mantenimiento',
        'evidencia_pdf',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_ejecucion' => 'date',
        'fecha_hora_realizacion' => 'datetime',
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

    public function usuarioFinaliza()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_usuario_finaliza', 'id_usuario');
    }

    public function actividades()
    {
        return $this->hasMany(MantenimientoActividad::class, 'fk_id_mantenimiento', 'id_mantenimiento')
            ->orderBy('numero_tarea');
    }

    public function estaRealizado(): bool
    {
        return in_array($this->estado_mantenimiento, ['Realizado', 'Finalizado'], true);
    }
}
