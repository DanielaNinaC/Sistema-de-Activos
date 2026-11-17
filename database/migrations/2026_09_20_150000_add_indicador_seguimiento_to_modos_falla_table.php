<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El indicador de seguimiento (Tabla 18) es propio del modo de falla, no del
 * tratamiento: vive en el catálogo y se copia una sola vez a
 * plan_tratamiento_riesgo.indicador_seguimiento (columna ya existente) al
 * generar la sugerencia automática, junto con estrategia_sugerida/accion_sugerida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modos_falla', function (Blueprint $table) {
            $table->string('indicador_seguimiento')->nullable()->after('justificacion_msr');
        });
    }

    public function down(): void
    {
        Schema::table('modos_falla', function (Blueprint $table) {
            $table->dropColumn('indicador_seguimiento');
        });
    }
};
