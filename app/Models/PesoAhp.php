<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesoAhp extends Model
{
    use HasFactory;

    protected $table = 'pesos_ahp';
    protected $primaryKey = 'id_config';

    protected $fillable = [
        'peso_severidad',
        'peso_frecuencia',
        'peso_msr',
        'peso_riesgo_tecnico',
        'peso_dependencia',
        'fecha_actualizacion',
        'vigente',
    ];

    protected $casts = [
        'vigente' => 'boolean',
    ];
}
