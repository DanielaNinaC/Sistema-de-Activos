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
        Schema::create('pesos_ahp', function (Blueprint $table) {
            $table->id('id_config');
            $table->decimal('peso_severidad', 5, 3)->default(0.648);
            $table->decimal('peso_frecuencia', 5, 3)->default(0.230);
            $table->decimal('peso_msr', 5, 3)->default(0.122);
            $table->dateTime('fecha_actualizacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesos_ahp');
    }
};
