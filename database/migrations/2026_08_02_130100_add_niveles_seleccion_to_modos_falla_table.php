<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Severidad y MSR dejan de ingresarse como número libre: se seleccionan
     * de opciones fijas (Tablas 20/22 de la tesis) y el sistema deriva el
     * valor numérico (valor_severidad_base / valor_msr_base) por mapeo fijo.
     */
    public function up(): void
    {
        Schema::table('modos_falla', function (Blueprint $table) {
            $table->string('nivel_severidad')->nullable()->after('valor_severidad_base');
            $table->string('nivel_msr')->nullable()->after('valor_msr_base');
            $table->text('justificacion_severidad')->nullable()->after('nivel_severidad');
            $table->text('justificacion_msr')->nullable()->after('nivel_msr');
        });
    }

    public function down(): void
    {
        Schema::table('modos_falla', function (Blueprint $table) {
            $table->dropColumn(['nivel_severidad', 'nivel_msr', 'justificacion_severidad', 'justificacion_msr']);
        });
    }
};
