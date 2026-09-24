<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivoTecnologico extends Model
{
    use HasFactory;

    protected $table = 'activos_tecnologicos';
    protected $primaryKey = 'id_activo';

    /**
     * 'criticidad_institucional' es un campo derivado de solo lectura: el
     * nivel de riesgo (Bajo/Medio/Alto/Crítico) de la última evaluación FMEA
     * vigente de este activo. NO es el nivel de dependencia institucional de
     * la categoría (eso vive en categorias.nivel_dependencia). Preferir
     * EvaluacionRiesgo::where('fk_id_activo', $id)->where('vigente', true)
     * ->max('score_calculado') como fuente de verdad en vez de este campo.
     */

    protected $fillable = [
        'fk_id_categoria',
        'fk_id_ubicacion',
        'codigo_patrimonial',
        'nombre_equipo',
        'marca',
        'modelo',
        'fecha_adquisicion',
        'estado_operativo',
        'etapa_ciclo_vida',
        'nivel_riesgo',
        'observaciones',
        'fecha_baja',
        'motivo_baja',
        'estado_previo_baja',
        'fk_id_usuario_baja',
    ];

    protected $casts = [
        'fecha_baja' => 'date',
    ];

    /**
     * Accesor y mutador de compatibilidad histórica para 'criticidad_institucional'.
     */
    public function getCriticidadInstitucionalAttribute(): ?string
    {
        return $this->attributes['nivel_riesgo'] ?? null;
    }

    public function setCriticidadInstitucionalAttribute(?string $value): void
    {
        $this->attributes['nivel_riesgo'] = $value;
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'fk_id_categoria', 'id_categoria');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'fk_id_ubicacion', 'id_ubicacion');
    }

    public function usuarioBaja()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_usuario_baja', 'id_usuario');
    }

    public function detalleComputadora()
    {
        return $this->hasOne(DetalleComputadora::class, 'fk_id_activo', 'id_activo');
    }

    public function detalleEquipoRed()
    {
        return $this->hasOne(DetalleEquipoRed::class, 'fk_id_activo', 'id_activo');
    }

    public function detalleProyector()
    {
        return $this->hasOne(DetalleProyector::class, 'fk_id_activo', 'id_activo');
    }

    public function incidentes()
    {
        return $this->hasMany(Incidente::class, 'fk_id_activo', 'id_activo');
    }

    public function evaluaciones()
    {
        return $this->hasMany(EvaluacionRiesgo::class, 'fk_id_activo', 'id_activo');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'fk_id_activo', 'id_activo');
    }

    /**
     * Fecha del mantenimiento más reciente ya ejecutado sobre este activo.
     * 'Finalizado' es el valor real usado en estado_mantenimiento en todo
     * el módulo de mantenimiento (ver resources/views/mantenimiento/*.blade.php);
     * 'Completado' nunca se usa como valor persistido.
     */
    public function fechaUltimoMantenimientoCompletado(): ?Carbon
    {
        $fecha = $this->mantenimientos()
            ->where('estado_mantenimiento', 'Finalizado')
            ->whereNotNull('fecha_ejecucion')
            ->max('fecha_ejecucion');

        return $fecha ? Carbon::parse($fecha) : null;
    }
}
