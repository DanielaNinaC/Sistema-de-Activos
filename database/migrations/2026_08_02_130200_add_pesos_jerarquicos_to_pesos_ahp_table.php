<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nivel 1 del score jerárquico (riesgo técnico vs dependencia
     * institucional) y bandera explícita de vigencia, para no depender de
     * orderByDesc('id_config') para saber cuál config está activa.
     */
    public function up(): void
    {
        Schema::table('pesos_ahp', function (Blueprint $table) {
            $table->decimal('peso_riesgo_tecnico', 5, 3)->default(0.750)->after('peso_msr');
            $table->decimal('peso_dependencia', 5, 3)->default(0.250)->after('peso_riesgo_tecnico');
            $table->boolean('vigente')->default(true)->after('fecha_actualizacion');
        });

        // Si ya existen configs previas, solo la más reciente queda vigente.
        $ultimoId = DB::table('pesos_ahp')->max('id_config');
        if ($ultimoId !== null) {
            DB::table('pesos_ahp')->where('id_config', '!=', $ultimoId)->update(['vigente' => false]);
            DB::table('pesos_ahp')->where('id_config', $ultimoId)->update(['vigente' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('pesos_ahp', function (Blueprint $table) {
            $table->dropColumn(['peso_riesgo_tecnico', 'peso_dependencia', 'vigente']);
        });
    }
};
