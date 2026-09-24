<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campo libre y opcional para el KPI de seguimiento del tratamiento (Tabla 18
 * del capítulo 3 del proyecto), ej. "Tasa de disponibilidad mensual".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_tratamiento_riesgo', function (Blueprint $table) {
            $table->string('indicador_seguimiento')->nullable()->after('accion_sugerida');
        });
    }

    public function down(): void
    {
        Schema::table('plan_tratamiento_riesgo', function (Blueprint $table) {
            $table->dropColumn('indicador_seguimiento');
        });
    }
};
