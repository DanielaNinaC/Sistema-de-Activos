<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Quitar tipo_equipo de detalles_equipo_red
        if (Schema::hasTable('detalles_equipo_red') && Schema::hasColumn('detalles_equipo_red', 'tipo_equipo')) {
            Schema::table('detalles_equipo_red', function (Blueprint $table) {
                $table->dropColumn('tipo_equipo');
            });
        }

        // 2. Quitar perifericos de detalles_computadora
        if (Schema::hasTable('detalles_computadora') && Schema::hasColumn('detalles_computadora', 'perifericos')) {
            Schema::table('detalles_computadora', function (Blueprint $table) {
                $table->dropColumn('perifericos');
            });
        }

        // 3. Agregar nuevas columnas booleanas a detalles_computadora (en closure separada)
        if (Schema::hasTable('detalles_computadora')) {
            Schema::table('detalles_computadora', function (Blueprint $table) {
                if (!Schema::hasColumn('detalles_computadora', 'lector')) {
                    $table->boolean('lector')->default(false);
                }
                if (!Schema::hasColumn('detalles_computadora', 'parlantes')) {
                    $table->boolean('parlantes')->default(false);
                }
                if (!Schema::hasColumn('detalles_computadora', 'teclado_mouse')) {
                    $table->boolean('teclado_mouse')->default(false);
                }
                if (!Schema::hasColumn('detalles_computadora', 'monitor')) {
                    $table->boolean('monitor')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('detalles_equipo_red')) {
            Schema::table('detalles_equipo_red', function (Blueprint $table) {
                if (!Schema::hasColumn('detalles_equipo_red', 'tipo_equipo')) {
                    $table->string('tipo_equipo')->nullable();
                }
            });
        }

        if (Schema::hasTable('detalles_computadora')) {
            Schema::table('detalles_computadora', function (Blueprint $table) {
                if (!Schema::hasColumn('detalles_computadora', 'perifericos')) {
                    $table->string('perifericos')->nullable();
                }
            });
        }

        if (Schema::hasTable('detalles_computadora')) {
            Schema::table('detalles_computadora', function (Blueprint $table) {
                $cols = ['lector', 'parlantes', 'teclado_mouse', 'monitor'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('detalles_computadora', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
