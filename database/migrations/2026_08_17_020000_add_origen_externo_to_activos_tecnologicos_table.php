<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clave estable de trazabilidad para activos importados desde fuentes
     * externas (ej. hojas de Excel de inventario real). Permite re-ejecutar
     * los seeders de importación de forma idempotente: si el código
     * patrimonial asignado cambia (p. ej. al renumerar códigos sintéticos),
     * el seeder puede seguir encontrando y actualizando la misma fila en vez
     * de duplicarla. Nula para activos creados manualmente desde la UI.
     */
    public function up(): void
    {
        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->string('origen_externo')->nullable()->unique()->after('codigo_patrimonial');
        });
    }

    public function down(): void
    {
        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->dropColumn('origen_externo');
        });
    }
};
