<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MantenimientoActividad extends Model
{
    use HasFactory;

    protected $table = 'mantenimiento_actividades';
    protected $primaryKey = 'id_actividad';

    protected $fillable = [
        'fk_id_mantenimiento',
        'numero_tarea',
        'tarea',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'fk_id_mantenimiento', 'id_mantenimiento');
    }
}
