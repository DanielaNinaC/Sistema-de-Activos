<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditoriaMuestra extends Model
{
    use HasFactory;

    protected $table = 'auditoria_muestra';

    public const MOTIVOS_INCLUSION = ['RIESGO_ALTO_CRITICO', 'ALEATORIO'];

    protected $fillable = [
        'fk_id_auditoria',
        'fk_id_activo',
        'nivel_riesgo_al_muestrear',
        'motivo_inclusion',
        'encontrado',
        'ubicacion_coincide',
        'estado_coincide',
        'observacion',
        'fk_id_verificado_por',
        'fecha_verificacion',
    ];

    protected $casts = [
        'encontrado' => 'boolean',
        'ubicacion_coincide' => 'boolean',
        'estado_coincide' => 'boolean',
        'fecha_verificacion' => 'datetime',
    ];

    public function auditoria()
    {
        return $this->belongsTo(AuditoriaInterna::class, 'fk_id_auditoria', 'id');
    }

    public function activo()
    {
        return $this->belongsTo(ActivoTecnologico::class, 'fk_id_activo', 'id_activo');
    }

    public function verificadoPor()
    {
        return $this->belongsTo(Usuario::class, 'fk_id_verificado_por', 'id_usuario');
    }

    /**
     * Verificada = el auditor ya marcó los 3 campos de verificación, sin
     * importar el resultado. Usado para el avance "X de N verificados" y
     * para bloquear aprobar() si la muestra no está al 100%.
     */
    public function estaVerificada(): bool
    {
        return !is_null($this->encontrado) && !is_null($this->ubicacion_coincide) && !is_null($this->estado_coincide);
    }
}
