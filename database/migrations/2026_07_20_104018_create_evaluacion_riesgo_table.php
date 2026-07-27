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
        Schema::create('evaluacion_riesgo', function (Blueprint $table) {
            $table->id('id_evaluacion');
            $table->unsignedBigInteger('fk_id_activo');
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->onDelete('cascade');
            $table->unsignedBigInteger('fk_id_modo_falla');
            $table->foreign('fk_id_modo_falla')->references('id_modo_falla')->on('modos_falla')->onDelete('cascade');
            $table->integer('valor_severidad');
            $table->integer('valor_frecuencia');
            $table->integer('valor_msr');
            $table->decimal('score_calculado', 5, 2);
            $table->string('nivel_riesgo');
            $table->dateTime('fecha_evaluacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_riesgo');
    }
};
