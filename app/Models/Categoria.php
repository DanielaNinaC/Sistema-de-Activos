<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';

    protected $fillable = [
        'nombre_categoria',
        'vida_util_meses',
        'nivel_dependencia',
        'justificacion_dependencia',
    ];

    public function activos()
    {
        return $this->hasMany(ActivoTecnologico::class, 'fk_id_categoria', 'id_categoria');
    }

    public function modosFalla()
    {
        return $this->hasMany(ModoFalla::class, 'fk_id_categoria', 'id_categoria');
    }
}
