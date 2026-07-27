<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes on columns frequently used in WHERE, ORDER BY and JOIN clauses
     * to speed up dashboard queries and list views across the system.
     */
    public function up(): void
    {
        Schema::table('evaluacion_riesgo', function (Blueprint $table) {
            $table->index('nivel_riesgo', 'idx_evaluacion_nivel_riesgo');
            $table->index('score_calculado', 'idx_evaluacion_score');
            $table->index('fecha_evaluacion', 'idx_evaluacion_fecha');
        });

        Schema::table('incidentes', function (Blueprint $table) {
            $table->index('estado_incidente', 'idx_incidentes_estado');
            $table->index('fecha_incidente', 'idx_incidentes_fecha');
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->index('estado_mantenimiento', 'idx_mantenimientos_estado');
            $table->index('fecha_programada', 'idx_mantenimientos_fecha');
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->index('tipo_alerta', 'idx_alertas_tipo');
            $table->index('fecha_generacion', 'idx_alertas_fecha');
        });

        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->index('estado_operativo', 'idx_activos_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluacion_riesgo', function (Blueprint $table) {
            $table->dropIndex('idx_evaluacion_nivel_riesgo');
            $table->dropIndex('idx_evaluacion_score');
            $table->dropIndex('idx_evaluacion_fecha');
        });

        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropIndex('idx_incidentes_estado');
            $table->dropIndex('idx_incidentes_fecha');
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropIndex('idx_mantenimientos_estado');
            $table->dropIndex('idx_mantenimientos_fecha');
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->dropIndex('idx_alertas_tipo');
            $table->dropIndex('idx_alertas_fecha');
        });

        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->dropIndex('idx_activos_estado');
        });
    }
};
