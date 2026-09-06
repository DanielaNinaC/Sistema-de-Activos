<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * "fecha_adquisicion" estaba modelada como YEAR (solo año). Se cambia a
     * DATE para poder registrar la fecha completa de adquisición. La
     * conversión del dato existente (asumiendo 1 de enero del año
     * registrado) se hace fila por fila en PHP, no con funciones SQL nativas
     * (STR_TO_DATE/CONCAT), para que la migración corra igual en MySQL y en
     * SQLite (usado por la suite de tests).
     */
    public function up(): void
    {
        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->date('fecha_adquisicion_tmp')->nullable()->after('fecha_adquisicion');
        });

        DB::table('activos_tecnologicos')
            ->whereNotNull('fecha_adquisicion')
            ->select('id_activo', 'fecha_adquisicion')
            ->orderBy('id_activo')
            ->each(function ($fila) {
                DB::table('activos_tecnologicos')
                    ->where('id_activo', $fila->id_activo)
                    ->update(['fecha_adquisicion_tmp' => $fila->fecha_adquisicion . '-01-01']);
            });

        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->dropColumn('fecha_adquisicion');
        });

        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->renameColumn('fecha_adquisicion_tmp', 'fecha_adquisicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->year('fecha_adquisicion_tmp')->nullable()->after('fecha_adquisicion');
        });

        DB::table('activos_tecnologicos')
            ->whereNotNull('fecha_adquisicion')
            ->select('id_activo', 'fecha_adquisicion')
            ->orderBy('id_activo')
            ->each(function ($fila) {
                DB::table('activos_tecnologicos')
                    ->where('id_activo', $fila->id_activo)
                    ->update(['fecha_adquisicion_tmp' => (int) substr($fila->fecha_adquisicion, 0, 4)]);
            });

        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->dropColumn('fecha_adquisicion');
        });

        Schema::table('activos_tecnologicos', function (Blueprint $table) {
            $table->renameColumn('fecha_adquisicion_tmp', 'fecha_adquisicion');
        });
    }
};
