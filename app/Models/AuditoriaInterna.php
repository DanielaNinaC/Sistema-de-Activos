<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditoriaInterna extends Model
{
    use HasFactory;

    protected $table = 'auditorias_internas';

    public const ESTADOS = ['BORRADOR', 'EN_REVISION', 'APROBADO'];
    public const OPINIONES = ['SATISFACTORIO', 'CON_OBSERVACIONES', 'INSATISFACTORIO'];

    protected $fillable = [
        'codigo',
        'gestion',
        'fecha_inicio',
        'fecha_fin',
        'fecha_corte',
        'objetivo',
        'alcance',
        'antecedentes',
        'metodologia',
        'limitaciones',
        'participantes',
        'buenas_practicas',
        'conclusiones',
        'opinion_calculada',
        'estado',
        'fk_id_auditor',
        'fk_id_responsable_ti',
        'fk_id_director',
        'fecha_aprobacion',
        'snapshot_indicadores',
        'fk_id_auditoria_anterior',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_corte' => 'date',
        'fecha_aprobacion' => 'datetime',
        'snapshot_indicadores' => 'array',
    ];

    public function auditor()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_auditor', 'id_usuario');
    }

    public function responsableTi()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_responsable_ti', 'id_usuario');
    }

    public function director()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_director', 'id_usuario');
    }

    public function auditoriaAnterior()
    {
        return $this->belongsTo(self::class, 'fk_id_auditoria_anterior', 'id');
    }

    public function muestra()
    {
        return $this->hasMany(AuditoriaMuestra::class, 'fk_id_auditoria', 'id');
    }

    public function hallazgos()
    {
        return $this->hasMany(HallazgoAuditoria::class, 'fk_id_auditoria', 'id');
    }

    public function esInmutable(): bool
    {
        return $this->estado === 'APROBADO';
    }
}
