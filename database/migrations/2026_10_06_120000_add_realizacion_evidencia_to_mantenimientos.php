<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Asegurar que el estado canónico 'Realizado' exista en el catálogo
        DB::table('estados_mantenimiento')->updateOrInsert(
            ['nombre' => 'Realizado'],
            [
                'es_abierto' => false,
                'es_finalizado' => true,
                'orden' => 4,
            ]
        );

        // 2. Añadir campos de trazabilidad a mantenimientos
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimientos', 'fecha_hora_realizacion')) {
                $table->dateTime('fecha_hora_realizacion')->nullable()->after('fecha_ejecucion');
            }
            if (!Schema::hasColumn('mantenimientos', 'fk_id_usuario_finaliza')) {
                $table->unsignedBigInteger('fk_id_usuario_finaliza')->nullable()->after('fk_id_usuario_asignado');
                $table->foreign('fk_id_usuario_finaliza')->references('id_usuario')->on('usuarios')->nullOnDelete();
            }
        });

        // 3. Crear tabla para almacenar las actividades ejecutadas del checklist
        if (!Schema::hasTable('mantenimiento_actividades')) {
            Schema::create('mantenimiento_actividades', function (Blueprint $table) {
                $table->id('id_actividad');
                $table->unsignedBigInteger('fk_id_mantenimiento');
                $table->unsignedTinyInteger('numero_tarea')->nullable();
                $table->string('tarea', 255);
                $table->timestamps();

                $table->foreign('fk_id_mantenimiento')
                    ->references('id_mantenimiento')
                    ->on('mantenimientos')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_actividades');

        Schema::table('mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('mantenimientos', 'fk_id_usuario_finaliza')) {
                $table->dropForeign(['fk_id_usuario_finaliza']);
                $table->dropColumn('fk_id_usuario_finaliza');
            }
            if (Schema::hasColumn('mantenimientos', 'fecha_hora_realizacion')) {
                $table->dropColumn('fecha_hora_realizacion');
            }
        });
    }
};
