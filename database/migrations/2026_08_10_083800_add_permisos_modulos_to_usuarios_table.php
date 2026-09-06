<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar columnas de permisos por módulo a la tabla usuarios.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('permiso_dashboard')->default(true)->after('estado');
            $table->boolean('permiso_activos')->default(true)->after('permiso_dashboard');
            $table->boolean('permiso_incidentes')->default(true)->after('permiso_activos');
            $table->boolean('permiso_riesgos')->default(true)->after('permiso_incidentes');
            $table->boolean('permiso_mantenimiento')->default(true)->after('permiso_riesgos');
            $table->boolean('permiso_informes')->default(true)->after('permiso_mantenimiento');
            $table->boolean('permiso_usuarios')->default(false)->after('permiso_informes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn([
                'permiso_dashboard',
                'permiso_activos',
                'permiso_incidentes',
                'permiso_riesgos',
                'permiso_mantenimiento',
                'permiso_informes',
                'permiso_usuarios',
            ]);
        });
    }
};
