<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El código (MotorFmeaService, modelo ActivoTecnologico) ya trata este campo
 * como 'nivel_riesgo' (derivado del motor FMEA, ya no editable a mano), pero
 * la migración que registra el cambio de nombre nunca se creó. La base de
 * desarrollo en MySQL ya tiene la columna renombrada manualmente; SQLite
 * (pruebas) y cualquier entorno nuevo todavía tienen 'criticidad_institucional'.
 * Esta migración cubre ambos casos sin duplicar la columna.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('activos_tecnologicos', 'criticidad_institucional')
            && ! Schema::hasColumn('activos_tecnologicos', 'nivel_riesgo')) {
            Schema::table('activos_tecnologicos', function (Blueprint $table) {
                $table->renameColumn('criticidad_institucional', 'nivel_riesgo');
            });

            return;
        }

        if (! Schema::hasColumn('activos_tecnologicos', 'nivel_riesgo')) {
            Schema::table('activos_tecnologicos', function (Blueprint $table) {
                $table->string('nivel_riesgo')->nullable()->after('etapa_ciclo_vida');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activos_tecnologicos', 'nivel_riesgo')
            && ! Schema::hasColumn('activos_tecnologicos', 'criticidad_institucional')) {
            Schema::table('activos_tecnologicos', function (Blueprint $table) {
                $table->renameColumn('nivel_riesgo', 'criticidad_institucional');
            });
        }
    }
};
