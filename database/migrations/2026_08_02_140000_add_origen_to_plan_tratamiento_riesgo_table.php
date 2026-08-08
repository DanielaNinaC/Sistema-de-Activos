<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distingue planes de tratamiento sugeridos automáticamente por el motor
     * FMEA (al evaluar un activo Alto/Crítico) de los creados manualmente
     * por un administrador.
     */
    public function up(): void
    {
        Schema::table('plan_tratamiento_riesgo', function (Blueprint $table) {
            $table->string('origen')->default('manual')->after('estado_tratamiento');
        });
    }

    public function down(): void
    {
        Schema::table('plan_tratamiento_riesgo', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
