<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('detalles_proyector', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_id_activo')->primary();
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->onDelete('cascade');
            $table->string('tipo_cable')->nullable();
            $table->integer('calidad_imagen')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('detalles_proyector');
    }
};