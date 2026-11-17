<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Actualización y Normalización de Usuarios SIATEC
 *
 * 1. Actualiza datos de usuarios institucionales (IDs 1, 2, 3, 7)
 * 2. Elimina usuario de prueba sin dependencias (ID 8)
 * 3. Crea tabla `estados_usuario` con registros iniciales
 * 4. Agrega FK `fk_id_estado_usuario` a `usuarios`, migra datos, elimina `estado`
 *
 * Usuarios intocables: ID 4 (Daniela Nina), ID 6 (Claudia Nina)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────────────
        // FASE 1: Actualizar datos de usuarios institucionales
        // ──────────────────────────────────────────────────────
        DB::table('usuarios')->where('id_usuario', 1)->update([
            'nombre'   => 'Marcelo',
            'apellido' => 'Monge',
            'correo'   => 'm.monje.franco@gmail.com',
        ]);

        DB::table('usuarios')->where('id_usuario', 2)->update([
            'nombre'   => 'Teofilo',
            'apellido' => 'Vazquez',
            'correo'   => 't.vazquez.franco@gmail.com',
        ]);

        DB::table('usuarios')->where('id_usuario', 3)->update([
            'nombre'   => 'Angela',
            'apellido' => 'Peña',
            'correo'   => 'a.peña.franco@gmail.com',
        ]);

        DB::table('usuarios')->where('id_usuario', 7)->update([
            'nombre'   => 'Fernando',
            'apellido' => 'Calle',
            'correo'   => 'f.calle.franco@gmail.com',
            'fk_id_rol' => 3, // Cambiar rol a Director
        ]);

        // ──────────────────────────────────────────────────────
        // FASE 2: Eliminar usuario de prueba sin dependencias
        // ──────────────────────────────────────────────────────
        DB::table('usuarios')->where('id_usuario', 8)->delete();

        // ──────────────────────────────────────────────────────
        // FASE 3: Crear tabla estados_usuario y normalizar
        // ──────────────────────────────────────────────────────
        if (!Schema::hasTable('estados_usuario')) {
            Schema::create('estados_usuario', function (Blueprint $table) {
                $table->id('id_estado_usuario');
                $table->string('nombre', 50)->unique();
                $table->string('descripcion', 255)->nullable();
                $table->timestamps();
            });

            DB::table('estados_usuario')->insert([
                [
                    'id_estado_usuario' => 1,
                    'nombre'            => 'Activo',
                    'descripcion'       => 'Usuario habilitado para utilizar el sistema',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ],
                [
                    'id_estado_usuario' => 2,
                    'nombre'            => 'Inactivo',
                    'descripcion'       => 'Usuario deshabilitado para utilizar el sistema',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ],
            ]);
        }

        // Agregar nueva columna FK
        if (!Schema::hasColumn('usuarios', 'fk_id_estado_usuario')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->unsignedBigInteger('fk_id_estado_usuario')->default(1)->after('contrasena');
                $table->foreign('fk_id_estado_usuario')
                      ->references('id_estado_usuario')
                      ->on('estados_usuario')
                      ->restrictOnDelete();
            });

            // Migrar datos: estado=1 → fk_id_estado_usuario=1, estado=0 → fk_id_estado_usuario=2
            DB::statement('UPDATE usuarios SET fk_id_estado_usuario = CASE WHEN estado = 1 THEN 1 ELSE 2 END');
        }

        // Eliminar columna booleana vieja
        if (Schema::hasColumn('usuarios', 'estado')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }
    }

    public function down(): void
    {
        // Restaurar columna booleana
        if (!Schema::hasColumn('usuarios', 'estado')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->boolean('estado')->default(true)->after('contrasena');
            });

            // Restaurar datos desde la FK
            if (Schema::hasColumn('usuarios', 'fk_id_estado_usuario')) {
                DB::statement('UPDATE usuarios SET estado = CASE WHEN fk_id_estado_usuario = 1 THEN 1 ELSE 0 END');
            }
        }

        // Eliminar FK y columna
        if (Schema::hasColumn('usuarios', 'fk_id_estado_usuario')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropForeign(['fk_id_estado_usuario']);
                $table->dropColumn('fk_id_estado_usuario');
            });
        }

        // Eliminar tabla estados_usuario
        Schema::dropIfExists('estados_usuario');

        // Nota: los datos de usuarios NO se restauran automáticamente en el down()
        // ya que eso requeriría almacenar los datos originales. Los datos de usuario
        // actualizados en up() permanecerán con sus nuevos valores.
    }
};
