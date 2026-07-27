<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id('id_mantenimiento');
            $table->unsignedBigInteger('fk_id_activo');
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->onDelete('cascade');
            $table->unsignedBigInteger('fk_id_usuario_asignado');
            $table->foreign('fk_id_usuario_asignado')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->unsignedBigInteger('id_evaluacion_origen')->nullable();
            $table->foreign('id_evaluacion_origen')->references('id_evaluacion')->on('evaluacion_riesgo')->onDelete('set null');
            $table->string('tipo_mantenimiento');
            $table->date('fecha_programada');
            $table->date('fecha_ejecucion')->nullable();
            $table->text('descripcion_trabajo')->nullable();
            $table->string('estado_mantenimiento')->default('Pendiente');
            $table->string('evidencia_pdf')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('mantenimientos');
    }
};