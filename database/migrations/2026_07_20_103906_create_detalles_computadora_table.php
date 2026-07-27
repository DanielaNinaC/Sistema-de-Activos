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
        Schema::create('detalles_computadora', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_id_activo')->primary();
            $table->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->onDelete('cascade');
            $table->integer('ram_gb')->nullable();
            $table->string('procesador')->nullable();
            $table->integer('disco_solido_gb')->nullable();
            $table->integer('disco_rigido_gb')->nullable();
            $table->string('sistema_operativo')->nullable();
            $table->string('perifericos')->nullable();
            $table->boolean('camara_web')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_computadora');
    }
};
