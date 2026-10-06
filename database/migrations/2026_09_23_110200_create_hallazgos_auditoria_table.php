<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgos del Informe General de Auditoría Interna de TI (Fase 2).
 * 'clasificacion_sugerida' la calcula el sistema (AuditoriaInternaService::
 * sugerirClasificacion) a partir del nivel de riesgo de los activos
 * vinculados; el auditor decide la 'clasificacion' final — nunca se
 * sobreescribe automáticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hallazgos_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_id_auditoria');
            $table->string('codigo')->unique();

            $table->string('apartado');
            $table->unsignedTinyInteger('requerimiento_ref')->nullable();

            $table->text('condicion');
            $table->text('criterio');
            $table->text('causa');
            $table->text('efecto');
            $table->text('evidencia');
            $table->text('recomendacion');

            $table->string('clasificacion');
            $table->string('clasificacion_sugerida')->nullable();
            $table->text('comentario_area')->nullable();

            $table->string('responsable');
            $table->date('fecha_compromiso')->nullable();

            $table->string('estado')->default('ABIERTO');
            $table->date('fecha_cierre')->nullable();

            $table->unsignedBigInteger('fk_id_hallazgo_origen')->nullable();

            $table->timestamps();

            $table->foreign('fk_id_auditoria')->references('id')->on('auditorias_internas')->cascadeOnDelete();
            $table->foreign('fk_id_hallazgo_origen')->references('id')->on('hallazgos_auditoria')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hallazgos_auditoria', function (Blueprint $table) {
            $table->dropForeign(['fk_id_hallazgo_origen']);
        });
        Schema::dropIfExists('hallazgos_auditoria');
    }
};
