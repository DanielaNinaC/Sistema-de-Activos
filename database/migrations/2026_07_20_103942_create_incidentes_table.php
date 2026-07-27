<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('incidentes', function (Blueprint $table) {
            $table->id('id_incidente');
            $table->unsignedBigInteger('fk_id_activo');
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->onDelete('cascade');
            $table->unsignedBigInteger('fk_id_usuario_reporta');
            $table->foreign('fk_id_usuario_reporta')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->dateTime('fecha_incidente');
            $table->text('descripcion_falla');
            $table->string('estado_incidente')->default('Reportado');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('incidentes');
    }
};