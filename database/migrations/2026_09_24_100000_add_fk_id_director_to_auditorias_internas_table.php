<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La sección de Aprobación y Distribución del Informe General debe mostrar
 * el nombre real de quien representa a Dirección (no un espacio en blanco
 * para firmar después). 'fk_id_auditor' y 'fk_id_responsable_ti' ya existían;
 * faltaba el equivalente para Dirección.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditorias_internas', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_id_director')->nullable()->after('fk_id_responsable_ti');
            $table->foreign('fk_id_director')->references('id_usuario')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auditorias_internas', function (Blueprint $table) {
            $table->dropForeign(['fk_id_director']);
            $table->dropColumn('fk_id_director');
        });
    }
};
