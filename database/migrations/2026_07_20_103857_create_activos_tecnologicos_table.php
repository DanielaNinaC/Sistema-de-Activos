<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up(): void {
        Schema::create('activos_tecnologicos', function (Blueprint $table) {
            $table->id('id_activo');
            $table->unsignedBigInteger('fk_id_categoria');
            $table->foreign('fk_id_categoria')->references('id_categoria')->on('categorias')->onDelete('cascade');
            $table->unsignedBigInteger('fk_id_ubicacion');
            $table->foreign('fk_id_ubicacion')->references('id_ubicacion')->on('ubicaciones')->onDelete('cascade');
            $table->string('codigo_patrimonial')->unique();
            $table->string('nombre_equipo');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->year('fecha_adquisicion')->nullable();
            $table->string('estado_operativo')->default('Operativo');
            $table->string('etapa_ciclo_vida')->default('Operación');
            $table->string('criticidad_institucional')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activos_tecnologicos');
    }
};
