<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos mínimos para trazabilidad del "Reporte de activos dados de baja".
 * 'estado_operativo' = 'De Baja' ya identifica la condición de baja (distinta
 * de los estados técnicos Óptimo/Regular/Obsoleto), pero no existía forma de
 * registrar cuándo, por qué ni quién la gestionó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->date('fecha_baja')->nullable()->after('etapa_ciclo_vida');
            $table->string('motivo_baja')->nullable()->after('fecha_baja');
            $table->string('estado_previo_baja')->nullable()->after('motivo_baja');
            $table->unsignedBigInteger('fk_id_usuario_baja')->nullable()->after('estado_previo_baja');

            $table->foreign('fk_id_usuario_baja')->references('id_usuario')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->dropForeign(['fk_id_usuario_baja']);
            $table->dropColumn(['fecha_baja', 'motivo_baja', 'estado_previo_baja', 'fk_id_usuario_baja']);
        });
    }
};
