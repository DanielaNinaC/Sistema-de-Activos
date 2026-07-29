<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleEquipoRed extends Model
{
    use HasFactory;

    protected $table = 'detalles_equipo_red';
    protected $primaryKey = 'fk_id_activo';
    public $incrementing = false;

    protected $fillable = [
        'fk_id_activo',
        'direccion_ip',
        'version_firmware',
    ];

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }
}
