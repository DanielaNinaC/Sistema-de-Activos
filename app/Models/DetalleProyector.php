<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleProyector extends Model
{
    use HasFactory;

    protected $table = 'detalles_proyector';
    protected $primaryKey = 'fk_id_activo';
    public $incrementing = false;

    protected $fillable = [
        'fk_id_activo',
        'tipo_cable',
        'calidad_imagen',
    ];

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }
}
