<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar valores base de Severidad (Tabla 20) y MSR (Tabla 22)
     * a cada modo de falla, para que el motor de inferencia FMEA-MSR
     * pueda calcular automáticamente sin intervención humana.
     */
    public function up(): void
    {
        Schema::table('modos_falla', function (Blueprint $table) {
            $table->integer('valor_severidad_base')->nullable()->after('nombre_modo_falla');
            $table->integer('valor_msr_base')->nullable()->after('valor_severidad_base');
        });
    }

    public function down(): void
    {
        Schema::table('modos_falla', function (Blueprint $table) {
            $table->dropColumn(['valor_severidad_base', 'valor_msr_base']);
        });
    }
};
