<?php

namespace Database\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Utilidades compartidas por las migraciones de normalización (Fase 3).
 *
 * Vive fuera de database/migrations porque el migrador carga todo archivo
 * `*_*.php` de esa carpeta como si fuera una migración. Cada migración lo
 * incluye con require_once.
 *
 * Todo está escrito con consultas portables (subconsultas correlacionadas,
 * sin UPDATE ... JOIN) porque las pruebas corren en SQLite y el sistema en MySQL.
 */
class NormalizacionMigracion
{
    /** Marcadores de "dato original vacío" (el modelo de datos exige marca y modelo no nulos). */
    public const SIN_MARCA = 'Sin marca';
    public const SIN_MODELO = '(sin modelo)';

    /** Columnas estándar de un catálogo (ver docs/modelo-normalizado-fase2.md §3.1). */
    public static function columnasCatalogo(Blueprint $table, string $pk): void
    {
        $table->id($pk);
        $table->string('codigo', 50)->unique();
        $table->string('nombre', 150)->unique();
        $table->string('descripcion')->nullable();
        $table->unsignedTinyInteger('orden')->default(0);
        $table->boolean('activo')->default(true);
        $table->timestamps();
    }

    /** `Obsolescencia` → `OBSOLESCENCIA`; `En revisión` → `EN_REVISION`. */
    public static function codigo(string $texto): string
    {
        $codigo = Str::upper(Str::ascii(trim($texto)));
        $codigo = preg_replace('/[^A-Z0-9]+/', '_', $codigo);

        return substr(trim($codigo, '_'), 0, 50) ?: 'SIN_CODIGO';
    }

    /** Código único dentro de una tabla: agrega _2, _3… si ya existe. */
    public static function codigoUnico(string $tabla, string $texto, string $columna = 'codigo'): string
    {
        $base = self::codigo($texto);
        $codigo = $base;
        $n = 2;

        while (DB::table($tabla)->where($columna, $codigo)->exists()) {
            $sufijo = '_' . $n++;
            $codigo = substr($base, 0, 50 - strlen($sufijo)) . $sufijo;
        }

        return $codigo;
    }

    /** Texto limpio: sin espacios sobrantes (incluye no separables) ni dobles. */
    public static function limpiar(?string $texto): string
    {
        $texto = str_replace("\u{00A0}", ' ', (string) $texto);

        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    /** Clave para agrupar variantes de escritura: minúsculas, sin acentos ni signos. */
    public static function clave(?string $texto): string
    {
        $texto = Str::lower(Str::ascii(self::limpiar($texto)));

        return preg_replace('/[^a-z0-9]+/', '', $texto);
    }

    /**
     * Dado [texto => cantidad], devuelve [clave => grafía canónica]: la más
     * frecuente; en empate, la que no está toda en mayúsculas; luego la primera
     * por orden alfabético.
     *
     * @param  array<string,int>  $conteos
     * @return array<string,string>
     */
    public static function canonicos(array $conteos): array
    {
        $grupos = [];
        foreach ($conteos as $texto => $cantidad) {
            $limpio = self::limpiar((string) $texto);
            if ($limpio === '') {
                continue;
            }
            $grupos[self::clave($limpio)][$limpio] = ($grupos[self::clave($limpio)][$limpio] ?? 0) + $cantidad;
        }

        $resultado = [];
        foreach ($grupos as $clave => $variantes) {
            // Más frecuente primero. En empate: las siglas cortas (HP, IBM, LG) van
            // en mayúsculas; los nombres largos, en la grafía que no grita.
            uksort($variantes, function ($a, $b) use ($variantes) {
                return [$variantes[$b], self::penalizacion($a), $a] <=> [$variantes[$a], self::penalizacion($b), $b];
            });
            $resultado[$clave] = array_key_first($variantes);
        }

        return $resultado;
    }

    /** 0 = grafía preferida en caso de empate. */
    private static function penalizacion(string $texto): int
    {
        $mayusculas = self::esTodoMayusculas($texto) === 1;
        $esSigla = mb_strlen(preg_replace('/[^\p{L}]/u', '', $texto)) <= 4;

        return $esSigla ? ($mayusculas ? 0 : 1) : ($mayusculas ? 1 : 0);
    }

    private static function esTodoMayusculas(string $texto): int
    {
        return ($texto === mb_strtoupper($texto) && $texto !== mb_strtolower($texto)) ? 1 : 0;
    }

    /**
     * Separa "Core i7- 2,70GHz" en ["Core i7", 2.70] y unifica mayúsculas
     * ("CORE i5" / "core i5" / "CORE I5" → "Core i5").
     *
     * @return array{0:string,1:?float}
     */
    public static function procesador(string $texto): array
    {
        $texto = self::limpiar($texto);
        $ghz = null;

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*ghz/i', $texto, $m)) {
            $ghz = (float) str_replace(',', '.', $m[1]);
            $texto = trim(str_ireplace($m[0], '', $texto), " -\t");
        }

        $nombre = Str::title(Str::lower($texto));
        $nombre = preg_replace('/\bI([3579])\b/', 'i$1', $nombre);

        return [self::limpiar($nombre), $ghz];
    }

    // ── Contracción (database/contraccion/) ─────────────────────────────

    /**
     * Aborta la contracción si alguna fila que tiene el texto original aún no
     * tiene su FK. Es la salvaguarda de "no borrar datos": la columna vieja
     * solo se elimina cuando todo su contenido ya vive en la columna nueva.
     */
    public static function verificarCompleto(string $tabla, string $fk, ?string $textoOrigen = null): void
    {
        if (!Schema::hasColumn($tabla, $fk)) {
            return;
        }

        $consulta = DB::table($tabla)->whereNull($fk);
        if ($textoOrigen !== null && Schema::hasColumn($tabla, $textoOrigen)) {
            $consulta->whereNotNull($textoOrigen)->whereRaw("TRIM({$textoOrigen}) <> ''");
        }

        $faltan = $consulta->count();
        if ($faltan > 0) {
            throw new \RuntimeException(
                "Contracción abortada: {$faltan} fila(s) de «{$tabla}» no tienen «{$fk}». "
                . 'Ejecute `php artisan normalizacion:sincronizar`, revise los datos y reintente.'
            );
        }
    }

    /** Elimina una columna junto con las FK e índices que la contienen (si existen). */
    public static function soltarColumna(string $tabla, string $columna): void
    {
        if (!self::tiene($tabla, $columna)) {
            return;
        }

        foreach (Schema::getForeignKeys($tabla) as $fk) {
            if (in_array($columna, $fk['columns'], true)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropForeign($fk['columns']));
            }
        }

        foreach (Schema::getIndexes($tabla) as $indice) {
            if (!$indice['primary'] && in_array($columna, $indice['columns'], true)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropIndex($indice['name']));
            }
        }

        Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn($columna));
    }

    /**
     * Rollback de una contracción: vuelve a crear la columna de texto (nullable)
     * y la rellena desde el catálogo a través de la FK. No re-crea el FK de texto.
     */
    public static function restaurarTexto(
        string $tabla,
        string $columna,
        string $fk,
        string $catalogo,
        string $pk,
        string $columnaCatalogo = 'nombre',
        int $longitud = 100
    ): void {
        if (self::tiene($tabla, $columna) || !self::tiene($tabla, $fk)) {
            return;
        }

        Schema::table($tabla, fn (Blueprint $t) => $t->string($columna, $longitud)->nullable());

        DB::statement("UPDATE {$tabla} SET {$columna} =
            (SELECT c.{$columnaCatalogo} FROM {$catalogo} c WHERE c.{$pk} = {$tabla}.{$fk})
            WHERE {$fk} IS NOT NULL");
    }

    /** Aborta si la consulta devuelve filas: "no borrar datos que aún no tienen destino". */
    public static function abortarSi(int $cantidad, string $mensaje): void
    {
        if ($cantidad > 0) {
            throw new \RuntimeException("Contracción abortada: {$cantidad} {$mensaje}");
        }
    }

    /** @param  array<int,string>  $columnas */
    public static function soltarColumnas(string $tabla, array $columnas): void
    {
        foreach ($columnas as $columna) {
            self::soltarColumna($tabla, $columna);
        }
    }

    public static function tieneIndice(string $tabla, string $indice): bool
    {
        foreach (Schema::getIndexes($tabla) as $i) {
            if ($i['name'] === $indice) {
                return true;
            }
        }

        return false;
    }

    /** ¿Existe la tabla y la columna? Permite que las migraciones se re-ejecuten. */
    public static function tiene(string $tabla, ?string $columna = null): bool
    {
        if (!Schema::hasTable($tabla)) {
            return false;
        }

        return $columna === null || Schema::hasColumn($tabla, $columna);
    }
}
