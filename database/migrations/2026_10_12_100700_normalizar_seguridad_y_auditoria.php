<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (8/8). Seguridad (módulos) y auditoría interna.
 *
 *  - roles.codigo: ADMIN / SOPORTE / DIRECTOR, derivado de los IDs que hoy
 *    fija config/roles.php (con respaldo por nombre). A partir de aquí el rol
 *    se identifica por código, no por un ID mágico de configuración.
 *  - rol_modulo: permisos por defecto por rol (reemplaza el arreglo literal de
 *    Usuario::permisosDefaultPorRol()).
 *  - usuario_modulo: permiso individual (reemplaza las 7 columnas permiso_*,
 *    un grupo repetido: 1FN).
 *  - Auditoría: estado, opinión, apartado, clasificación, motivo y nivel de
 *    riesgo pasan a FK de sus catálogos.
 */
return new class extends Migration
{
    /** Permisos por defecto de cada rol (copia 1:1 de Usuario::permisosDefaultPorRol). */
    private const PERMISOS_POR_ROL = [
        'ADMIN' => ['DASHBOARD', 'ACTIVOS', 'INCIDENTES', 'RIESGOS', 'MANTENIMIENTO', 'INFORMES', 'USUARIOS'],
        'SOPORTE' => ['DASHBOARD', 'ACTIVOS', 'INCIDENTES', 'RIESGOS', 'MANTENIMIENTO'],
        'DIRECTOR' => ['DASHBOARD', 'INFORMES'],
        // Cualquier otro rol: todo excepto gestión de usuarios.
        '*' => ['DASHBOARD', 'ACTIVOS', 'INCIDENTES', 'RIESGOS', 'MANTENIMIENTO', 'INFORMES'],
    ];

    /** [tabla, columna nueva, catálogo, PK, columna de texto, columna del catálogo] */
    private const ENLACES_AUDITORIA = [
        ['auditorias_internas', 'fk_id_estado_auditoria', 'estados_auditoria', 'id_estado_auditoria', 'estado', 'codigo'],
        ['auditorias_internas', 'fk_id_opinion_auditoria', 'opiniones_auditoria', 'id_opinion_auditoria', 'opinion_calculada', 'codigo'],
        ['auditoria_muestra', 'fk_id_motivo_inclusion', 'motivos_inclusion_muestra', 'id_motivo_inclusion', 'motivo_inclusion', 'codigo'],
        ['auditoria_muestra', 'fk_id_nivel_riesgo', 'niveles_riesgo', 'id_nivel_riesgo', 'nivel_riesgo_al_muestrear', 'nombre'],
        ['hallazgos_auditoria', 'fk_id_apartado', 'apartados_auditoria', 'id_apartado', 'apartado', 'numero'],
        ['hallazgos_auditoria', 'fk_id_clasificacion', 'clasificaciones_hallazgo', 'id_clasificacion_hallazgo', 'clasificacion', 'codigo'],
        ['hallazgos_auditoria', 'fk_id_clasificacion_sugerida', 'clasificaciones_hallazgo', 'id_clasificacion_hallazgo', 'clasificacion_sugerida', 'codigo'],
        ['hallazgos_auditoria', 'fk_id_estado_hallazgo', 'estados_hallazgo', 'id_estado_hallazgo', 'estado', 'codigo'],
    ];

    public function up(): void
    {
        if (N::tiene('rol_modulo')) {
            return;
        }

        Schema::table('roles', fn (Blueprint $t) => $t->string('codigo', 50)->nullable());

        Schema::create('rol_modulo', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_rol');
            $t->unsignedBigInteger('fk_id_modulo');
            $t->timestamps();

            $t->primary(['fk_id_rol', 'fk_id_modulo']);
            $t->foreign('fk_id_rol')->references('id_rol')->on('roles')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreign('fk_id_modulo')->references('id_modulo')->on('modulos')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('usuario_modulo', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_usuario');
            $t->unsignedBigInteger('fk_id_modulo');
            $t->boolean('habilitado')->default(true);
            $t->timestamps();

            $t->primary(['fk_id_usuario', 'fk_id_modulo']);
            $t->foreign('fk_id_usuario')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreign('fk_id_modulo')->references('id_modulo')->on('modulos')->cascadeOnUpdate()->restrictOnDelete();
        });

        foreach (self::ENLACES_AUDITORIA as [$tabla, $nueva]) {
            Schema::table($tabla, fn (Blueprint $t) => $t->unsignedBigInteger($nueva)->nullable());
        }

        $this->poblar();

        Schema::table('roles', fn (Blueprint $t) => $t->unique('codigo'));

        foreach (self::ENLACES_AUDITORIA as [$tabla, $nueva, $catalogo, $pk]) {
            Schema::table($tabla, function (Blueprint $t) use ($nueva, $catalogo, $pk) {
                $t->foreign($nueva)->references($pk)->on($catalogo)->cascadeOnUpdate()->restrictOnDelete();
            });
        }
    }

    /** Idempotente. */
    public function poblar(): void
    {
        $this->poblarRoles();
        $this->poblarPermisos();

        foreach (self::ENLACES_AUDITORIA as [$tabla, $nueva, $catalogo, $pk, $texto, $columna]) {
            DB::statement("UPDATE {$tabla} SET {$nueva} =
                (SELECT c.{$pk} FROM {$catalogo} c WHERE c.{$columna} = {$tabla}.{$texto})
                WHERE {$nueva} IS NULL AND {$texto} IS NOT NULL");
        }
    }

    private function poblarRoles(): void
    {
        $ids = [
            'ADMIN' => (int) config('roles.admin', 1),
            'SOPORTE' => (int) config('roles.soporte', 2),
            'DIRECTOR' => (int) config('roles.director', 3),
        ];

        foreach (DB::table('roles')->whereNull('codigo')->orderBy('id_rol')->get() as $rol) {
            $nombre = mb_strtolower($rol->nombre_rol);
            $codigo = array_search((int) $rol->id_rol, $ids, true);

            if ($codigo === false) {
                $codigo = match (true) {
                    str_contains($nombre, 'admin') => 'ADMIN',
                    str_contains($nombre, 'soporte') => 'SOPORTE',
                    str_contains($nombre, 'director') => 'DIRECTOR',
                    default => N::codigo($rol->nombre_rol),
                };
            }

            // Evita violar el UNIQUE si dos roles resolvieron al mismo código.
            $codigo = DB::table('roles')->where('codigo', $codigo)->exists()
                ? N::codigoUnico('roles', $codigo)
                : $codigo;

            DB::table('roles')->where('id_rol', $rol->id_rol)->update(['codigo' => $codigo]);
        }
    }

    private function poblarPermisos(): void
    {
        $ahora = now();
        $modulo = DB::table('modulos')->pluck('id_modulo', 'codigo');

        foreach (DB::table('roles')->get() as $rol) {
            $modulos = self::PERMISOS_POR_ROL[$rol->codigo] ?? self::PERMISOS_POR_ROL['*'];
            $yaTiene = DB::table('rol_modulo')->where('fk_id_rol', $rol->id_rol)->pluck('fk_id_modulo')->all();

            foreach ($modulos as $codigo) {
                if (isset($modulo[$codigo]) && !in_array($modulo[$codigo], $yaTiene, true)) {
                    DB::table('rol_modulo')->insert([
                        'fk_id_rol' => $rol->id_rol, 'fk_id_modulo' => $modulo[$codigo],
                        'created_at' => $ahora, 'updated_at' => $ahora,
                    ]);
                }
            }
        }

        // Permiso individual: una fila por usuario y módulo, tomada de las 7 columnas permiso_*.
        foreach ($modulo as $codigo => $idModulo) {
            $columna = 'permiso_' . strtolower($codigo);
            if (!Schema::hasColumn('usuarios', $columna)) {
                continue;
            }

            $yaRegistrados = DB::table('usuario_modulo')->where('fk_id_modulo', $idModulo)->pluck('fk_id_usuario')->all();

            foreach (DB::table('usuarios')->get(['id_usuario', $columna]) as $u) {
                if (in_array($u->id_usuario, $yaRegistrados, true)) {
                    continue;
                }

                DB::table('usuario_modulo')->insert([
                    'fk_id_usuario' => $u->id_usuario, 'fk_id_modulo' => $idModulo,
                    'habilitado' => (bool) $u->{$columna}, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::ENLACES_AUDITORIA) as [$tabla, $nueva]) {
            Schema::table($tabla, function (Blueprint $t) use ($nueva) {
                $t->dropForeign([$nueva]);
                $t->dropColumn($nueva);
            });
        }

        Schema::dropIfExists('usuario_modulo');
        Schema::dropIfExists('rol_modulo');

        Schema::table('roles', function (Blueprint $t) {
            $t->dropUnique(['codigo']);
            $t->dropColumn('codigo');
        });
    }
};
