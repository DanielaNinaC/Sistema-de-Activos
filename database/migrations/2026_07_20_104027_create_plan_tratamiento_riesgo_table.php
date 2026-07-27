<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('plan_tratamiento_riesgo', function (Blueprint $table) {
            $table->id('id_tratamiento');
            $table->unsignedBigInteger('fk_id_evaluacion');
            $table->foreign('fk_id_evaluacion')->references('id_evaluacion')->on('evaluacion_riesgo')->onDelete('cascade');
            $table->string('estrategia');
            $table->text('accion_preventiva');
            $table->string('estado_tratamiento')->default('Pendiente');
            $table->date('fecha_compromiso')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('plan_tratamiento_riesgo');
    }
};