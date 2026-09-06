<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Priorización de la atención de incidentes (Alta / Media / Baja).
     *
     * Se guarda como string y se valida por lista blanca en el controlador,
     * mismo patrón que estado_incidente y que estrategia en los planes de
     * tratamiento: evita un ENUM en BD que obligue a migrar cada vez que
     * cambie el vocabulario.
     */
    public function up(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->string('prioridad')->default('Media')->after('estado_incidente');
        });
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropColumn('prioridad');
        });
    }
};
