<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HallazgoAuditoria extends Model
{
    use HasFactory;

    protected $table = 'hallazgos_auditoria';

    public const APARTADOS = ['7.1', '7.2', '7.3', '7.4', '7.5', '7.6'];
    public const CLASIFICACIONES = ['NC_MAYOR', 'NC_MENOR', 'OBSERVACION', 'OPORTUNIDAD_MEJORA'];
    public const ESTADOS = ['ABIERTO', 'EN_PROCESO', 'CERRADO'];

    protected $fillable = [
        'fk_id_auditoria',
        'codigo',
        'apartado',
        'requerimiento_ref',
        'condicion',
        'criterio',
        'causa',
        'efecto',
        'evidencia',
        'recomendacion',
        'clasificacion',
        'clasificacion_sugerida',
        'comentario_area',
        'responsable',
        'fecha_compromiso',
        'estado',
        'fecha_cierre',
        'fk_id_hallazgo_origen',
    ];

    protected $casts = [
        'fecha_compromiso' => 'date',
        'fecha_cierre' => 'date',
    ];

    public function auditoria()
    {
        return $this->belongsTo(AuditoriaInterna::class, 'fk_id_auditoria', 'id');
    }

    public function hallazgoOrigen()
    {
        return $this->belongsTo(self::class, 'fk_id_hallazgo_origen', 'id');
    }

    public function activos()
    {
        return $this->belongsToMany(ActivoTecnologico::class, 'hallazgo_activo', 'fk_id_hallazgo', 'fk_id_activo', 'id', 'id_activo');
    }
}
