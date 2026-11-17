<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auditorias_internas')) {
            Schema::table('auditorias_internas', function (Blueprint $table) {
                if (!Schema::hasColumn('auditorias_internas', 'auditor')) {
                    $table->string('auditor', 255)->nullable()->after('estado');
                }
            });

            // Llenar datos iniciales para auditorias existentes
            if (Schema::hasColumn('auditorias_internas', 'fk_id_auditor')) {
                DB::statement("
                    UPDATE auditorias_internas a
                    LEFT JOIN usuarios u ON a.fk_id_auditor = u.id_usuario
                    SET a.auditor = CASE
                        WHEN u.nombre LIKE '%Daniela%' THEN 'Carlos Apaza'
                        WHEN u.nombre IS NOT NULL THEN TRIM(CONCAT(u.nombre, ' ', COALESCE(u.apellido, '')))
                        ELSE 'Carlos Apaza'
                    END
                    WHERE a.auditor IS NULL OR a.auditor = ''
                ");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('auditorias_internas') && Schema::hasColumn('auditorias_internas', 'auditor')) {
            Schema::table('auditorias_internas', function (Blueprint $table) {
                $table->dropColumn('auditor');
            });
        }
    }
};
