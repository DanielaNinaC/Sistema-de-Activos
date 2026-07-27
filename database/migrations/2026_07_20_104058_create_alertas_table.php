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
        Schema::create('alertas', function (Blueprint $table) {
            $table->id('id_alerta');
            $table->unsignedBigInteger('fk_id_evaluacion');
            $table->foreign('fk_id_evaluacion')->references('id_evaluacion')->on('evaluacion_riesgo')->onDelete('cascade');
            $table->string('tipo_alerta');
            $table->dateTime('fecha_generacion');
            $table->boolean('notificacion_enviada')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
