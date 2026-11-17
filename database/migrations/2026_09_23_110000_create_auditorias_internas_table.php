<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla principal del Informe General de Auditoría Interna de TI (Fase 2 del
 * módulo de Auditoría Interna). Vocabularios (estado, opinion_calculada) son
 * columnas string con lista blanca en el modelo, no ENUM de BD — misma
 * convención que el resto del sistema (ver docs/bitacora-tecnica.md §1.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias_internas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->unsignedSmallInteger('gestion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_corte');

            $table->text('objetivo')->nullable();
            $table->text('alcance')->nullable();
            $table->text('antecedentes')->nullable();
            $table->text('metodologia')->nullable();
            $table->text('limitaciones')->nullable();
            $table->text('participantes')->nullable();
            $table->text('buenas_practicas')->nullable();
            $table->text('conclusiones')->nullable();

            $table->string('opinion_calculada')->nullable();
            $table->string('estado')->default('BORRADOR');

            $table->unsignedBigInteger('fk_id_auditor');
            $table->unsignedBigInteger('fk_id_responsable_ti')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->json('snapshot_indicadores')->nullable();
            $table->unsignedBigInteger('fk_id_auditoria_anterior')->nullable();

            $table->timestamps();

            $table->foreign('fk_id_auditor')->references('id_usuario')->on('usuarios')->restrictOnDelete();
            $table->foreign('fk_id_responsable_ti')->references('id_usuario')->on('usuarios')->nullOnDelete();
            $table->foreign('fk_id_auditoria_anterior')->references('id')->on('auditorias_internas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auditorias_internas', function (Blueprint $table) {
            $table->dropForeign(['fk_id_auditoria_anterior']);
        });
        Schema::dropIfExists('auditorias_internas');
    }
};
