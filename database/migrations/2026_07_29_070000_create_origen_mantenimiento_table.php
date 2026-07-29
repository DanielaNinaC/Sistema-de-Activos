<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('origen_mantenimiento', function (Blueprint $table) {
            $table->id('id_origen');
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        DB::table('origen_mantenimiento')->insert([
            ['id_origen' => 1, 'nombre' => 'Preventivo', 'created_at' => now(), 'updated_at' => now()],
            ['id_origen' => 2, 'nombre' => 'Evaluación de riesgos', 'created_at' => now(), 'updated_at' => now()],
            ['id_origen' => 3, 'nombre' => 'Incidente', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('origen_mantenimiento');
    }
};
