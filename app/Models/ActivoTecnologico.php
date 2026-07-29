<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivoTecnologico extends Model
{
    use HasFactory;

    protected $table = 'activos_tecnologicos';
    protected $primaryKey = 'id_activo';

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
        'criticidad_institucional',
        'observaciones',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'fk_id_categoria', 'id_categoria');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'fk_id_ubicacion', 'id_ubicacion');
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
}
