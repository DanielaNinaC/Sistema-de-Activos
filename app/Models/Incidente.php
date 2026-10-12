<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incidente extends Model
{
    use HasFactory;

    protected $table = 'incidentes';
    protected $primaryKey = 'id_incidente';

    protected $fillable = [
        'fk_id_activo',
        'fk_id_usuario_reporta',
        'fecha_incidente',
        'descripcion_falla',
        'fk_id_modo_falla',
        'estado_incidente',
        'prioridad',
        'fecha_cierre',
    ];

    protected $casts = [
        'fecha_incidente' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }

    public function usuarioReporta()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_usuario_reporta', 'id_usuario');
    }

    public function modoFalla()
    {
        return $this->belongsTo(ModoFalla::class, 'fk_id_modo_falla', 'id_modo_falla');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'fk_id_incidente', 'id_incidente');
    }
}
