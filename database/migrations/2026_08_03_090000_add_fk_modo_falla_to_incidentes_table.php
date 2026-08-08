<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etiquetado opcional del incidente con el modo de falla real (Módulo
     * de Informes lo usa para "modos de falla más frecuentes" con datos
     * reales, en vez de solo el matching de texto libre del motor FMEA).
     * Los incidentes históricos sin este campo caen en "Sin clasificar" —
     * no se migran datos retroactivamente.
     */
    public function up(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_id_modo_falla')->nullable()->after('descripcion_falla');
            $table->foreign('fk_id_modo_falla')->references('id_modo_falla')->on('modos_falla')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropForeign(['fk_id_modo_falla']);
            $table->dropColumn('fk_id_modo_falla');
        });
    }
};
