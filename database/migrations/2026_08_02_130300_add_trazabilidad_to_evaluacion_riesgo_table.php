<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trazabilidad real de evaluaciones: en vez de sobrescribir la fila de
     * (activo, modo_falla) en cada corrida del motor, se conserva el
     * historial completo y se marca cuál es la vigente.
     */
    public function up(): void
    {
        Schema::table('evaluacion_riesgo', function (Blueprint $table) {
            $table->boolean('vigente')->default(true)->after('nivel_riesgo');
            $table->boolean('conteo_generico')->default(false)->after('fecha_evaluacion');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion_riesgo', function (Blueprint $table) {
            $table->dropColumn(['vigente', 'conteo_generico']);
        });
    }
};
