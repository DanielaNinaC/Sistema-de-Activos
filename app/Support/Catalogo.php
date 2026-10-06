<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lectura de las tablas de parámetros (catálogos) del sistema: vocabularios
 * de estados, tipos y prioridades, y las tablas de la tesis que parametrizan
 * el motor FMEA (severidad, MSR, frecuencia, dependencia, niveles de riesgo,
 * tratamiento sugerido).
 *
 * Es la única fuente de verdad de esos valores: ni los controladores ni las
 * vistas deben volver a declararlos como constantes o listas literales.
 * Cada tabla se lee una sola vez por petición (son tablas de 3 a 6 filas).
 */
class Catalogo
{
    /** @var array<string, Collection> */
    private static array $cache = [];

    public static function flush(?string $tabla = null): void
    {
        if ($tabla === null) {
            self::$cache = [];
            return;
        }

        unset(self::$cache[$tabla]);
    }

    /** Filas de un catálogo, ordenadas por su columna `orden`. */
    public static function filas(string $tabla): Collection
    {
        return self::$cache[$tabla] ??= DB::table($tabla)->orderBy('orden')->get();
    }

    /** Valores de una columna (por defecto `nombre`), en el orden del catálogo. */
    public static function nombres(string $tabla, string $columna = 'nombre'): array
    {
        return self::filas($tabla)->pluck($columna)->all();
    }

    public static function fila(string $tabla, ?string $clave, string $columna = 'nombre'): ?object
    {
        if ($clave === null) {
            return null;
        }

        return self::filas($tabla)->first(fn ($f) => $f->{$columna} === $clave);
    }

    /** Nombres de las filas que tienen activada la bandera indicada. */
    public static function conBandera(string $tabla, string $bandera, bool $valor = true): array
    {
        return self::filas($tabla)
            ->filter(fn ($f) => (bool) $f->{$bandera} === $valor)
            ->pluck('nombre')
            ->values()
            ->all();
    }

    /** Texto para un desplegable: "Nombre (descripción)" o solo el nombre si no tiene descripción. */
    public static function etiqueta(object $fila): string
    {
        $descripcion = $fila->descripcion ?? null;

        return $descripcion ? "{$fila->nombre} ({$descripcion})" : $fila->nombre;
    }

    // ── Incidentes ──────────────────────────────────────────────────────

    /** Estados con los que se puede registrar un incidente nuevo (los que no lo cierran). */
    public static function estadosIncidenteAbiertos(): array
    {
        return self::filas('estados_incidente')->filter(fn ($f) => !$f->cierra_incidente)->values()->all();
    }

    /** Estados que sacan a un incidente del seguimiento activo. */
    public static function estadosIncidenteCerrados(): array
    {
        return self::conBandera('estados_incidente', 'cierra_incidente');
    }

    /** Orden de atención por prioridad: nombre => peso (1 = más urgente). */
    public static function pesoPrioridad(): array
    {
        return self::filas('prioridades_incidente')->pluck('peso', 'nombre')->all();
    }

    /**
     * Prioridad sugerida para un incidente según el nivel de riesgo del
     * activo. Se indexa por el nivel de riesgo ('Crítico') y por su
     * criticidad equivalente ('Crítica'), que son las dos formas con que el
     * sistema ha referido ese mismo valor.
     */
    public static function prioridadSugeridaPorNivel(): array
    {
        $mapa = [];
        foreach (self::filas('niveles_riesgo') as $nivel) {
            $mapa[$nivel->nombre] = $nivel->prioridad_sugerida;
            $mapa[$nivel->criticidad] = $nivel->prioridad_sugerida;
        }

        return $mapa;
    }

    // ── Niveles de riesgo ───────────────────────────────────────────────

    /** Niveles (Alto, Crítico) que requieren un plan de tratamiento y cuentan como riesgos graves. */
    public static function nivelesQueRequierenTratamiento(): array
    {
        return self::conBandera('niveles_riesgo', 'requiere_tratamiento');
    }

    /** ¿Una alerta de este nivel se notifica de inmediato por correo? */
    public static function nivelNotificaAlerta(?string $nivel): bool
    {
        return (bool) (self::fila('niveles_riesgo', $nivel)->notifica_alerta ?? false);
    }

    // ── Mantenimiento ───────────────────────────────────────────────────

    /** Mantenimientos que siguen pendientes de ejecutarse. */
    public static function estadosMantenimientoAbiertos(): array
    {
        return self::conBandera('estados_mantenimiento', 'es_abierto');
    }

    /** Estados con los que se puede registrar un mantenimiento nuevo (todos menos 'Cancelado'). */
    public static function estadosMantenimientoParaRegistro(): array
    {
        return self::filas('estados_mantenimiento')
            ->filter(fn ($f) => $f->es_abierto || $f->es_finalizado)
            ->values()
            ->all();
    }

    /** Estados que significan "el trabajo se ejecutó". */
    public static function estadosMantenimientoFinalizados(): array
    {
        return self::conBandera('estados_mantenimiento', 'es_finalizado');
    }

    public static function origenMantenimientoId(string $nombre): ?int
    {
        $id = DB::table('origen_mantenimiento')->where('nombre', $nombre)->value('id_origen');

        return $id === null ? null : (int) $id;
    }

    // ── Tratamiento de riesgo ───────────────────────────────────────────

    public static function estadosTratamientoAbiertos(): array
    {
        return self::conBandera('estados_tratamiento', 'es_abierto');
    }

    /** Estrategias que se cierran sin ejecutar un Mantenimiento sobre el activo. */
    public static function estrategiasSinMantenimiento(): array
    {
        return self::conBandera('estrategias_tratamiento', 'genera_mantenimiento', false);
    }

    public static function estrategiaGeneraMantenimiento(?string $estrategia): bool
    {
        return (bool) (self::fila('estrategias_tratamiento', $estrategia)->genera_mantenimiento ?? false);
    }

    /**
     * Tratamiento que el motor sugiere para un nivel de riesgo (columna
     * "Acción del Sistema" de la Tabla 29). Null si el nivel no genera
     * borrador automático.
     */
    public static function tratamientoSugerido(string $nivel): ?array
    {
        $fila = self::filas('tratamientos_sugeridos')->first(fn ($f) => $f->nivel_riesgo === $nivel);

        return $fila ? [
            'estrategia' => $fila->estrategia,
            'accion_preventiva' => $fila->accion_preventiva,
            'dias_plazo' => (int) $fila->dias_plazo,
        ] : null;
    }

    /** Tratamiento sugerido de cada nivel que lo define: nivel => [estrategia, accion_preventiva, dias_plazo]. */
    public static function mapaTratamientoSugerido(): array
    {
        return self::filas('tratamientos_sugeridos')
            ->mapWithKeys(fn ($f) => [$f->nivel_riesgo => self::tratamientoSugerido($f->nivel_riesgo)])
            ->all();
    }

    // ── Motor FMEA (tablas de la tesis) ─────────────────────────────────

    /** Valor numérico de Severidad (Tabla 20) para un nivel, p. ej. 'muy_alta' => 10. */
    public static function valorSeveridad(?string $nivel): ?int
    {
        $fila = self::fila('niveles_severidad', $nivel, 'clave');

        return $fila ? (int) $fila->valor : null;
    }

    /** Valor numérico de MSR (Tabla 22) para un nivel, p. ej. 'muy_baja' => 9. */
    public static function valorMsr(?string $nivel): ?int
    {
        $fila = self::fila('niveles_msr', $nivel, 'clave');

        return $fila ? (int) $fila->valor : null;
    }

    /** Valor de Dependencia Institucional (Tabla 8) para 'Alta'/'Media'/'Baja'. */
    public static function valorDependencia(?string $nivel): ?int
    {
        $fila = self::fila('niveles_dependencia', $nivel);

        return $fila ? (int) $fila->valor : null;
    }

    /**
     * Frecuencia dinámica (Tabla 21) según el desgaste del activo y sus
     * incidentes. Las reglas se evalúan en orden; la última no tiene
     * condiciones y actúa como valor por defecto.
     */
    public static function frecuencia(float $porcentajeDesgaste, int $incidentes): int
    {
        foreach (self::filas('niveles_frecuencia') as $regla) {
            if ($regla->operador === null) {
                return (int) $regla->valor;
            }

            $cumpleDesgaste = $porcentajeDesgaste >= (float) $regla->desgaste_minimo;
            $cumpleIncidentes = $incidentes >= (int) $regla->incidentes_minimo;

            $cumple = $regla->operador === 'AND'
                ? ($cumpleDesgaste && $cumpleIncidentes)
                : ($cumpleDesgaste || $cumpleIncidentes);

            if ($cumple) {
                return (int) $regla->valor;
            }
        }

        return 2;
    }

    /**
     * Nivel de riesgo (Tabla 29) para un score: el de mayor umbral que el
     * score alcance. Devuelve nivel, color del semáforo y criticidad.
     */
    public static function nivelPorScore(float $score): array
    {
        $niveles = self::filas('niveles_riesgo')->sortByDesc(fn ($n) => (float) $n->puntaje_minimo);

        $nivel = $niveles->first(fn ($n) => $score >= (float) $n->puntaje_minimo)
            ?? $niveles->last();

        return [
            'nivel' => $nivel->nombre,
            'tipo_alerta' => $nivel->tipo_alerta,
            'criticidad' => $nivel->criticidad,
            'color_hex' => self::fila('tipos_alerta', $nivel->tipo_alerta)->color_hex ?? '#94a3b8',
        ];
    }
}
