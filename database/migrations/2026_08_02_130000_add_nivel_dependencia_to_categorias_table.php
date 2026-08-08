<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nivel de dependencia institucional de la categoría (Nivel 1 del score
     * jerárquico: Score_final = p1*SubscoreTecnico + p2*D_escalado).
     */
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('nivel_dependencia')->default('Media')->after('vida_util_meses');
            $table->text('justificacion_dependencia')->nullable()->after('nivel_dependencia');
        });

        // Backfill según Tabla 8 de la tesis. Se usa coincidencia parcial de
        // nombre porque las categorías reales no siempre calzan literalmente
        // con los nombres de la tabla (ej. "PC" en vez de "Computadoras").
        $alta = ['servidor', 'router', 'switch', 'red', 'punto de acceso', 'computador', 'pc', 'laptop', 'camara', 'cctv'];
        $media = ['tablet', 'impresora', 'proyector'];

        foreach ($alta as $termino) {
            DB::table('categorias')
                ->whereRaw('LOWER(nombre_categoria) LIKE ?', ['%' . $termino . '%'])
                ->update(['nivel_dependencia' => 'Alta']);
        }

        foreach ($media as $termino) {
            DB::table('categorias')
                ->whereRaw('LOWER(nombre_categoria) LIKE ?', ['%' . $termino . '%'])
                ->update(['nivel_dependencia' => 'Media']);
        }
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn(['nivel_dependencia', 'justificacion_dependencia']);
        });
    }
};
