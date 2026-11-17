<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deja constancia de qué se sugirió vs. qué se aplicó realmente en cada
 * tratamiento, sin tabla de historial aparte: cada evaluación ya genera su
 * propio PlanTratamientoRiesgo (ver FASE 0), así que el historial completo
 * sale de recorrer las evaluaciones de un activo con su tratamiento asociado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_tratamiento_riesgo', function (Blueprint $table) {
            $table->string('estrategia_sugerida')->nullable()->after('estrategia');
            $table->text('accion_sugerida')->nullable()->after('accion_preventiva');
            $table->text('observacion_cierre')->nullable()->after('estado_tratamiento');
        });
    }

    public function down(): void
    {
        Schema::table('plan_tratamiento_riesgo', function (Blueprint $table) {
            $table->dropColumn(['estrategia_sugerida', 'accion_sugerida', 'observacion_cierre']);
        });
    }
};
