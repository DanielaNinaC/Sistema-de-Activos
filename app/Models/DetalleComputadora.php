<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleComputadora extends Model
{
    use HasFactory;

    protected $table = 'detalles_computadora';
    protected $primaryKey = 'fk_id_activo';
    public $incrementing = false;

    protected $fillable = [
        'fk_id_activo',
        'ram_gb',
        'procesador',
        'disco_solido_gb',
        'disco_rigido_gb',
        'sistema_operativo',
        'lector',
        'parlantes',
        'teclado_mouse',
        'monitor',
        'camara_web',
    ];

    protected $casts = [
        'lector' => 'boolean',
        'parlantes' => 'boolean',
        'teclado_mouse' => 'boolean',
        'monitor' => 'boolean',
        'camara_web' => 'boolean',
    ];

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }
}
