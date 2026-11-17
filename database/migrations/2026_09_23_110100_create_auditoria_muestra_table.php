<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Muestra de verificación física del inventario (Fase 2). Un activo entra a
 * la muestra por riesgo Alto/Crítico o al azar (motivo_inclusion); el nivel
 * de riesgo se congela en 'nivel_riesgo_al_muestrear' porque puede cambiar
 * después de generada la muestra y el informe no debe reflejar ese cambio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_muestra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_id_auditoria');
            $table->unsignedBigInteger('fk_id_activo');

            $table->string('nivel_riesgo_al_muestrear');
            $table->string('motivo_inclusion');

            $table->boolean('encontrado')->nullable();
            $table->boolean('ubicacion_coincide')->nullable();
            $table->boolean('estado_coincide')->nullable();
            $table->text('observacion')->nullable();

            $table->unsignedBigInteger('fk_id_verificado_por')->nullable();
            $table->timestamp('fecha_verificacion')->nullable();

            $table->timestamps();

            $table->foreign('fk_id_auditoria')->references('id')->on('auditorias_internas')->cascadeOnDelete();
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->cascadeOnDelete();
            $table->foreign('fk_id_verificado_por')->references('id_usuario')->on('usuarios')->nullOnDelete();

            $table->unique(['fk_id_auditoria', 'fk_id_activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_muestra');
    }
};
