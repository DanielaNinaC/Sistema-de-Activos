<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 'incidentes' solo tenía 'fecha_incidente' (apertura). El indicador de
 * Tiempo Medio de Resolución del módulo de Auditoría Interna de TI (RA-03 y
 * el indicador 7.4 del Informe General) es imprescindible y no se puede
 * calcular sin una fecha de cierre — se agrega esta única columna nueva en
 * vez de inventar un valor. Se puebla automáticamente cuando el incidente
 * pasa a un estado de ProcesoActivoController::ESTADOS_INCIDENTE_CERRADOS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->timestamp('fecha_cierre')->nullable()->after('estado_incidente');
        });
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropColumn('fecha_cierre');
        });
    }
};
