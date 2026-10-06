<?php

namespace Database\Seeders;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\DetalleProyector;
use App\Models\Ubicacion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Carga el inventario de proyectores (categoría 9) desde el inventario 2025.
 *
 *   php artisan db:seed --class=ProyectoresSeeder
 *
 * Es atómico (todo o nada) e idempotente: la clave es codigo_patrimonial, así
 * que ejecutarlo de nuevo actualiza los mismos activos sin duplicarlos.
 * No ejecuta evaluaciones de riesgo (nivel_riesgo queda NULL).
 */
class ProyectoresSeeder extends Seeder
{
    private const ID_CATEGORIA = 9;

    /** Ubicación que se crea si no existe; cualquier otra que falte detiene la carga. */
    private const UBICACION_CREABLE = ['Secundaria', '24'];

    private const ESTADOS = [
        1 => ['texto' => 'Buen estado', 'operativo' => 'Operativo', 'etapa' => 'Operación'],
        2 => ['texto' => 'Equipo antiguo', 'operativo' => 'Operativo', 'etapa' => 'Renovación / Retiro'],
        3 => ['texto' => 'Requiere cambio inmediato', 'operativo' => 'Operativo', 'etapa' => 'Renovación / Retiro'],
    ];

    /** Nombres legibles para las aulas del inventario que no son un código de aula. */
    private const NOMBRES_ESPECIALES = [
        'S PROFES' => 'Sala de Profesores',
        'S. REU. ADM' => 'Sala de Reuniones ADM',
        'S. REU. DIR' => 'Sala de Reuniones DIR',
        'ARTES' => 'Artes',
        'MUSICA' => 'Música',
        'TEATRO' => 'Teatro',
    ];

    /** Nº|Aula inventario|Sección destino|Aula destino|Marca|Modelo|Código|Año|Cable|Estado|Calidad|Nota */
    private const INVENTARIO = <<<'TABLA'
1|P2|Maternal|P2|Epson|S17|8852|2015|HDMI|1|8|
3|P4|Maternal|P4|Epson|S17|8891|2015|HDMI|1|6|
4|P5|Maternal|P5|Epson|W52+|13147|2024|HDMI|1||
5|P6|Maternal|P6|Epson|E20|12389|2023|HDMI|1||
6|P7|Maternal|P7|Epson|S18+|8953|2016|USB|3|4|
7|P9|Maternal|P9|Epson|E20|13024|2024|HDMI|1||
8|BCD P10|BCD|10|Casio|XJF211WN|11841|2022|HDMI|1||
10|p12|Maternal|P12|Epson|S12+|5636|2013|VGA|2|7|
11|S PROFES|Maternal|Sala de Profesores Maternal|Casio|XJ-V1|4714|2018|HDMI|1||
13|P19|Primaria|19|Casio|XJF211WN|11797|2022|HDMI|1||
14|P20|Primaria|20|Epson|S17|8876|2015|HDMI|2|7|
15|P21|Primaria|21|Epson|S17|8851|2015|USB|1||
16|P22|Primaria|22|Epson|S17|8911|2015|HDMI|1||
17|P23|Primaria|23|Casio|XJ-V2|2046|2019|VGA|1||Entrada HDMI dañada.
18|P24|Primaria|24|Epson|X27|614|2017|HDMI|1||
19|P25|Primaria|25|Casio|XJ-V2|11829|2022|HDMI|1||
20|P26|Primaria|26|Epson|S18+|8952|2016|HDMI|2|7|
21|P27|Primaria|27|Epson|S17|8920|2015|HDMI|2|8|
22|P28|Primaria|28|Epson|S12+|8516|2013|VGA|2|6|
23|P29|Primaria|29|Epson|EB-685W|13840|2025|HDMI|1||
24|P30|Primaria|30|Epson|EB-685W|13841|2025|USB|1||
25|P31|Primaria|31|Epson|EB-685W|13842|2025|USB|1||
26|P32|Primaria|32|Epson|W52+|13146|2024|HDMI|1||
27|P33|Primaria|33|Casio|XJ-F20XN|11796|2022|HDMI|1||
28|S1|Primaria|S1|Epson|E20|12384|2023|HDMI|1||
29|S2|Primaria|S2|Epson|S17|5195||HDMI|1||
30|S3|Primaria|S3|Epson|EB-685W|13843|2025|HDMI|1||
31|S4|Primaria|S4|Epson|EB-685W|13844|2025|HDMI|1||
32|S5|Primaria|S5|Casio|XJ-F20XN|11839|2022|HDMI|1||
33|S7|Secundaria|7|Epson|S17|8921|2015|HDMI|1||
34|S8|Secundaria|8|Casio|XJ-V2|10033|2020|HDMI|1||
35|S9|Secundaria|9|Casio|XJ-V2|2256|2019|HDMI|1||
36|S10|Secundaria|10|Epson|E20|13022|2024|HDMI|1||
37|S11|Secundaria|11|Epson|X27|9000|2017|HDMI|1||
38|S12|Secundaria|12|Casio|XJ-V2|10038|2020|HDMI|1||
39|S13|Secundaria|13|Epson|W52+|13145|2024|HDMI|1||
40|S14|Secundaria|14|Casio|XJ-V1|920|2018|HDMI|1||
41|S15|Secundaria|15|Casio|XJF211WN|11795|2022|HDMI|1||
42|S16|Secundaria|16|Casio|XJF211WN||2022|HDMI|1||
43|S17|Secundaria|17|Casio|XJ-V2|1165|2018|HDMI|1||
44|S18|Secundaria|18|Casio|XJ-V2|2255|2019|HDMI|1||
45|S19|Secundaria|Laboratorio Secundaria S-19|Casio|XJ-V2|2254|2019|VGA|1||
46|S20|Secundaria|20|Casio|XJ-V1|921|2018|HDMI|1||
47|S21|Secundaria|21|Casio|XJ-V2|2047|2019|HDMI|1||
48|S23|Secundaria|23|Epson|S17|8888|2015|HDMI|2|6|
49|S24|Secundaria|24|Epson|S17|8922|2015|HDMI|2|5|
50|26|Secundaria|26|Casio|XJ-V2|2253|2019|HDMI|1||
51|27|Secundaria|27|Epson|X27|3|2017|HDMI|2|6|
52|B01|Secundaria|B01|Casio|XJ-V2|10030|2020|HDMI|1||
53|B02|Secundaria|B02|Casio|XJ-V2|10039|2020|HDMI|1||
54|B03|Secundaria|B03|Casio|XJ-V2|2043|2019|HDMI|1||
55|B04|Secundaria|B04|Casio|XJ-V2|2045|2019|HDMI|1||
56|B05|Secundaria|B05|Casio|XJ-V2|10031|2020|HDMI|1||
57|ARTES|Secundaria|Artes Plásticas|Casio|XJ-V2|10040|2020|HDMI|1||
58|MUSICA|Secundaria|Música|Casio|XJ-V2|10041|2020|HDMI|1||
59|A01|Secundaria|A01|Casio|XJ-V2|10006|2020|HDMI|1||
60|A02|Secundaria|A02|Casio|XJ-V2|1164|2018|HDMI|1||
61|A03|Secundaria|A03|Epson|S17|8850|2015|HDMI|1||
62|S. REU. ADM|Comunicación|Sala de Reuniones|Epson|S17|8889|2015|HDMI|2|8|
63|S. REU. DIR|Comunicación|Sala de Reuniones|Casio|XJ-V2|11840|2022|HDMI|1||
64|CDI|Secundaria|CDI|Casio|XJ-V2|10034|2020|HDMI|1||
65|TEATRO|Comunicación|Teatro|Epson|L630U|12697|2023|HDMI|1||
TABLA;

    /** @var string[] */
    private array $advertencias = [];

    private int $creados = 0;
    private int $actualizados = 0;

    public function run(): void
    {
        $filas = $this->leerInventario();

        // Semilla fija: las fechas aleatorias son las mismas en cada ejecución.
        mt_srand(2026);

        DB::transaction(function () use ($filas) {
            $categoria = Categoria::find(self::ID_CATEGORIA);
            if (!$categoria || stripos($categoria->nombre_categoria, 'proyector') === false) {
                throw new RuntimeException('La categoría ' . self::ID_CATEGORIA . ' no existe o no es la de Proyectores.');
            }

            $ubicaciones = $this->resolverUbicaciones($filas);

            foreach ($filas as $fila) {
                $this->cargarProyector($fila, $ubicaciones[$fila['seccion'] . '|' . $fila['aula']]);
            }
        });

        $this->resumen();
    }

    /** @return array<int, array<string, mixed>> */
    private function leerInventario(): array
    {
        $filas = [];

        foreach (preg_split('/\R/', trim(self::INVENTARIO)) as $linea) {
            $c = array_map('trim', explode('|', $linea));

            $filas[] = [
                'n' => (int) $c[0],
                'aula_inv' => $c[1],
                'seccion' => $c[2],
                'aula' => $c[3],
                'marca' => $c[4],
                'modelo' => $c[5],
                'codigo' => $c[6],
                'anio' => $c[7],
                'cable' => $c[8],
                'estado' => (int) $c[9],
                'calidad' => $c[10],
                'nota' => $c[11] ?? '',
            ];
        }

        if (count($filas) !== 62) {
            $this->advertencias[] = 'Se esperaban 62 proyectores y la tabla trae ' . count($filas) . '.';
        }

        return $filas;
    }

    /**
     * Busca cada ubicación destino por (sección, aula). Si faltan, se detiene
     * antes de guardar nada, salvo la única que se permite crear.
     *
     * @return array<string, Ubicacion>
     */
    private function resolverUbicaciones(array $filas): array
    {
        $resueltas = [];
        $faltantes = [];

        foreach ($filas as $fila) {
            $clave = $fila['seccion'] . '|' . $fila['aula'];
            if (isset($resueltas[$clave]) || isset($faltantes[$clave])) {
                continue;
            }

            $ubicacion = Ubicacion::where('seccion', $fila['seccion'])->where('aula', $fila['aula'])->first();

            if (!$ubicacion && [$fila['seccion'], $fila['aula']] === self::UBICACION_CREABLE) {
                $ubicacion = Ubicacion::firstOrCreate(['seccion' => $fila['seccion'], 'aula' => $fila['aula']]);
                $this->advertencias[] = "Ubicación creada: {$fila['seccion']} / {$fila['aula']}.";
            }

            if ($ubicacion) {
                $resueltas[$clave] = $ubicacion;
            } else {
                $faltantes[$clave] = "{$fila['seccion']} / {$fila['aula']} (Nº {$fila['n']})";
            }
        }

        if ($faltantes) {
            throw new RuntimeException(
                "Faltan ubicaciones en la tabla `ubicaciones`; no se cargó nada:\n  - " . implode("\n  - ", $faltantes)
            );
        }

        return $resueltas;
    }

    private function cargarProyector(array $fila, Ubicacion $ubicacion): void
    {
        $aulaInv = mb_strtoupper($fila['aula_inv']);
        $estado = self::ESTADOS[$fila['estado']] ?? null;

        if (!$estado) {
            throw new RuntimeException("Nº {$fila['n']}: estado de inventario '{$fila['estado']}' no reconocido.");
        }

        // Código patrimonial: el del inventario o uno provisional.
        $sinCodigo = $fila['codigo'] === '';
        $codigo = $sinCodigo ? 'PROY-SC-' . $aulaInv : 'PROY-' . $fila['codigo'];

        if ($sinCodigo) {
            $this->advertencias[] = "Nº {$fila['n']} ({$fila['aula_inv']}): sin código patrimonial → código provisional {$codigo}.";
        } elseif (mb_strlen($fila['codigo']) < 3) {
            $this->advertencias[] = "Nº {$fila['n']} ({$fila['aula_inv']}): código \"{$fila['codigo']}\" posiblemente incompleto; verificar.";
        }

        // Fecha de adquisición: aleatoria dentro del año de compra, reproducible.
        $sinAnio = $fila['anio'] === '';
        $fecha = null;

        if ($sinAnio) {
            $this->advertencias[] = "Nº {$fila['n']} ({$fila['aula_inv']}): sin año de compra → fecha_adquisicion NULL.";
        } else {
            $fecha = Carbon::create((int) $fila['anio'], mt_rand(1, 12), mt_rand(1, 28))->startOfDay();
            if ($fecha->isFuture()) {
                $fecha = Carbon::today();
            }
        }

        $observaciones = array_filter([
            'Estado según inventario 2025: ' . $estado['texto'] . '.',
            $fila['nota'],
            $sinCodigo ? 'Sin código patrimonial en el inventario; código provisional asignado.' : null,
            $sinAnio ? 'Año de compra no registrado en el inventario.' : null,
        ]);

        // origen_externo no está en $fillable del modelo: se asigna sin protección de asignación masiva.
        $activo = ActivoTecnologico::unguarded(fn () => ActivoTecnologico::updateOrCreate(
            ['codigo_patrimonial' => $codigo],
            [
                'fk_id_categoria' => self::ID_CATEGORIA,
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'origen_externo' => 'PROYECTORES|' . mb_strtoupper($fila['seccion']) . '|' . $aulaInv . '|1',
                'nombre_equipo' => 'Proyector ' . $this->nombreLegible($aulaInv),
                'marca' => $fila['marca'],
                'modelo' => $fila['modelo'],
                'fecha_adquisicion' => $fecha?->toDateString(),
                'estado_operativo' => $estado['operativo'],
                'etapa_ciclo_vida' => $estado['etapa'],
                'observaciones' => implode(' ', $observaciones),
                'nivel_riesgo' => null,
            ]
        ));

        $activo->wasRecentlyCreated ? $this->creados++ : $this->actualizados++;

        DetalleProyector::updateOrCreate(
            ['fk_id_activo' => $activo->id_activo],
            [
                'tipo_cable' => $fila['cable'],
                'calidad_imagen' => $fila['calidad'] === '' ? null : (int) $fila['calidad'],
            ]
        );
    }

    private function nombreLegible(string $aulaInventarioMayusculas): string
    {
        return self::NOMBRES_ESPECIALES[$aulaInventarioMayusculas] ?? $aulaInventarioMayusculas;
    }

    private function resumen(): void
    {
        $consola = $this->command;

        $consola->newLine();
        $consola->info('Carga de proyectores finalizada');
        $consola->table(['Concepto', 'Cantidad'], [
            ['Proyectores en la categoría ' . self::ID_CATEGORIA, ActivoTecnologico::where('fk_id_categoria', self::ID_CATEGORIA)->count()],
            ['Creados en esta ejecución', $this->creados],
            ['Actualizados (ya existían)', $this->actualizados],
        ]);

        $consola->warn('Advertencias (' . count($this->advertencias) . '):');
        foreach ($this->advertencias as $advertencia) {
            $consola->line('  - ' . $advertencia);
        }

        $consola->newLine();
        $consola->info('Verificación: activos de la categoría ' . self::ID_CATEGORIA);

        $consola->table(
            ['codigo_patrimonial', 'nombre_equipo', 'seccion', 'aula', 'tipo_cable', 'calidad_imagen', 'etapa_ciclo_vida'],
            DB::table('activos_tecnologicos as a')
                ->join('ubicaciones as u', 'u.id_ubicacion', '=', 'a.fk_id_ubicacion')
                ->leftJoin('detalles_proyector as d', 'd.fk_id_activo', '=', 'a.id_activo')
                ->where('a.fk_id_categoria', self::ID_CATEGORIA)
                ->orderBy('a.id_activo')
                ->get(['a.codigo_patrimonial', 'a.nombre_equipo', 'u.seccion', 'u.aula', 'd.tipo_cable', 'd.calidad_imagen', 'a.etapa_ciclo_vida'])
                ->map(fn ($f) => array_map(fn ($v) => $v ?? '—', (array) $f))
                ->all()
        );
    }
}
