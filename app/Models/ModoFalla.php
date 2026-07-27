<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModoFalla extends Model
{
    use HasFactory;

    protected $table = 'modos_falla';
    protected $primaryKey = 'id_modo_falla';

    protected $fillable = [
        'fk_id_categoria',
        'nombre_modo_falla',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'fk_id_categoria', 'id_categoria');
    }
}
