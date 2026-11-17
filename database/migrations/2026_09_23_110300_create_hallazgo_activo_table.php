<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivote hallazgo <-> activos afectados. Se eligió una tabla propia en vez de
 * guardar referencias en texto libre dentro de 'evidencia': permite que
 * AuditoriaInternaService::sugerirClasificacion() consulte el nivel de riesgo
 * real de los activos vinculados sin parsear texto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hallazgo_activo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_id_hallazgo');
            $table->unsignedBigInteger('fk_id_activo');
            $table->timestamps();

            $table->foreign('fk_id_hallazgo')->references('id')->on('hallazgos_auditoria')->cascadeOnDelete();
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->cascadeOnDelete();

            $table->unique(['fk_id_hallazgo', 'fk_id_activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hallazgo_activo');
    }
};
