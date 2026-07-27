<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('modos_falla', function (Blueprint $table) {
            $table->id('id_modo_falla');
            $table->unsignedBigInteger('fk_id_categoria');
            $table->foreign('fk_id_categoria')->references('id_categoria')->on('categorias')->onDelete('cascade');
            $table->string('nombre_modo_falla');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('modos_falla');
    }
};