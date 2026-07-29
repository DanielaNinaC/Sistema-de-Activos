<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_id_origen')->default(1)->after('fk_id_usuario_asignado');
            $table->foreign('fk_id_origen')->references('id_origen')->on('origen_mantenimiento')->onDelete('restrict');

            $table->unsignedBigInteger('fk_id_incidente')->nullable()->after('id_evaluacion_origen');
            $table->foreign('fk_id_incidente')->references('id_incidente')->on('incidentes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['fk_id_incidente']);
            $table->dropColumn('fk_id_incidente');
            $table->dropForeign(['fk_id_origen']);
            $table->dropColumn('fk_id_origen');
        });
    }
};
