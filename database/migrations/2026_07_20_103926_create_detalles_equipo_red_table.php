<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('detalles_equipo_red', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_id_activo')->primary();
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->onDelete('cascade');
            $table->string('direccion_ip')->nullable();
            $table->string('version_firmware')->nullable();
            $table->string('tipo_equipo')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('detalles_equipo_red');
    }
};