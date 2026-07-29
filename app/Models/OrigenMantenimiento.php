<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrigenMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'origen_mantenimiento';
    protected $primaryKey = 'id_origen';

    protected $fillable = [
        'nombre',
    ];

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'fk_id_origen', 'id_origen');
    }
}
