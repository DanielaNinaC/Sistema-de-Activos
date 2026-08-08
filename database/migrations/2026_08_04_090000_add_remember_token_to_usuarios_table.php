<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columna que Laravel usa para la sesión persistente ("Recordarme").
     * Sin ella, Auth::attempt($credenciales, true) falla al intentar guardar
     * el token. El nombre lo define Authenticatable::getRememberTokenName().
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->rememberToken()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
