<?php

namespace Database\Seeders;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\DetalleEquipoRed;
use App\Models\Ubicacion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Carga el inventario de impresoras (categoría 8): 50 equipos.
 *
 *   php artisan db:seed --class=ImpresorasSeeder
 *
 * Atómico (todo o nada) e idempotente: la clave es codigo_patrimonial en
 * activos_tecnologicos y fk_id_activo en detalles_equipo_red.
 * No ejecuta evaluaciones de riesgo (nivel_riesgo queda NULL).
 */
class ImpresorasSeeder extends Seeder
{
    use \Database\Seeders\Concerns\SincronizaNormalizacion;

    private const ID_CATEGORIA = 8;

    private const ESTADOS = [
        1 => ['texto' => 'Buen funcionamiento', 'etapa' => 'Operación'],
        2 => ['texto' => 'Equipo antiguo, se sugiere cambiar piezas para mejorar el rendimiento', 'etapa' => 'Mantenimiento'],
        3 => ['texto' => 'Obsoleta, se sugiere cambio de equipo', 'etapa' => 'Renovación / Retiro'],
    ];

    /** Conteo esperado por estado del inventario (validación previa a insertar). */
    private const CONTEO_ESTADOS = [1 => 33, 2 => 8, 3 => 9];

    /**
     * Nº|Sección|Aula|Nombre equipo|Marca|Modelo|Código|Año|IP|Estado|Nota
     * Un asterisco al final del aula marca una ubicación que aún no existe y se crea.
     */
    private const INVENTARIO = <<<'TABLA'
1|Maternal|P5|Impresora Maternal P5|Epson|L380||2018||3|
2|Maternal|P7|Impresora Maternal P7|Epson|L380|1237|2018||1|
3|Maternal|Sala de Profesores Maternal|Impresora Sala de Profesores Maternal|Epson|WF-5710||2024||1|
4|BCD|10|Impresora BCD 10|HP|LaserJet Pro M402dn|496|2017||1|
5|Primaria|19|Impresora Primaria 19|Epson|L6270||2019|130.130.50.102|1|
6|Primaria|24|Impresora Primaria 24|Epson|L6171||2021|130.130.50.140|1|
7|Primaria|29|Impresora Primaria 29|Epson|L6171|2291|2019|130.130.50.23|1|
8|Primaria|P1 Intermedio|Impresora Primaria P1|Epson|L6171||2021|130.130.50.121|1|Aula "1" del inventario asignada a P1 Intermedio.
9|Secundaria|Laboratorio Secundaria S-19|Impresora Laboratorio S-19|HP|LaserJet Pro M402dn|489|2017||1|
10|Secundaria|Laboratorio EXAO|Impresora Laboratorio EXAO #1|Epson|L3110||2021||1|
11|Secundaria|Laboratorio EXAO|Impresora Laboratorio EXAO #2|HP|LaserJet P1606dn|8086|2011|130.130.40.122|2|
12|Secundaria|CDI|Impresora CDI|Epson|WF-5710||2022||3|
13|Secundaria|CDI|Escáner CDI BAC|Canon|imageFORMULA DR-6030C||2021||1|Equipo escáner.
14|Enfermería|Enfermería|Impresora Enfermería|HP|LaserJet 1020||2012||1|Estado vacío en el inventario; se asume Buen funcionamiento.
15|Vida Escolar|Vida Escolar|Impresora Vida Escolar Secretaría|Epson|L355||2015||2|
16|Vida Escolar|Vida Escolar|Impresora Vida Escolar Registro|Epson|L220||2016||2|
17|Dirección|DIR-SEC|Impresora Dirección Secundaria|Epson|L3250||2023||1|
18|Góndola|Vero|Impresora Góndola|HP|LaserJet 1010||2012||2|
19|Psicóloga||Impresora Psicóloga|Epson|L210||2015||1|
20|Informática|Sarah|Impresora Informática #1|Epson|L6171||2020||1|
21|Informática|Sarah|Impresora Informática #2|Epson|L3250||2022||1|
22|Informática|Sarah|Impresora Informática #3|Epson|L220||2016||3|
23|Informática|Sarah|Impresora Informática #4|Epson|L210||2015||3|
24|Informática|Sarah|Impresora Informática #5|Epson|L210||2015||3|
25|Informática|Sarah|Impresora Informática #6|Epson|T24||2012||3|
26|Informática|Sarah|Impresora Informática #7|HP|LaserJet P1606dn||2014||2|
27|Informática|Sarah|Impresora Informática #8|HP|LaserJet P1606dn||2014||2|
28|Informática|Sarah|Impresora Informática #9|HP|LaserJet P1606dn||2014||3|
29|Informática|Sarah|Impresora Informática #10|HP|LaserJet P1606dn||2014||3|
30|Informática|Sarah|Impresora Informática #11|HP|LaserJet 1010||2012||3|
31|Informática|Sarah|Impresora Informática #12|HP|LaserJet Pro M402dn||2017||1|
32|Administración|Reservas*|Impresora Reservas|HP|LaserJet P1606dn||2014||1|
33|Administración|Hall*|Impresora Hall Administración|Epson|L6171||2020||1|
34|Administración|Caja|Impresora Caja|Epson|L2110||2022||1|
35|Administración|Mensajería*|Impresora Mensajería|HP|LaserJet 1010||2012||2|
36|Administración|Carmen*|Impresora Carmen|Epson|L575||2018||1|
37|Administración|Viki*|Impresora Viki|HP|LaserJet 1015||2012||1|
38|Administración|DAF|Impresora Administración #7|Epson|L380||2016||1|Sin aula en el inventario; ubicación provisional DAF.
39|Administración|DAF|Escáner Administración #8|HP|ScanJet Pro 3000 s3||2020||1|Equipo escáner. Sin aula en el inventario; ubicación provisional DAF.
40|Administración|Contabilidad*|Impresora Contabilidad|HP|LaserJet P1606dn||2014||1|
41|Administración|DAF Adjunto*|Impresora DAF Adjunto|HP|LaserJet P1606dn||2022||1|Marca corregida: el inventario indicaba EPSON para un modelo LaserJet de HP.
42|Administración|DAF|Impresora DAF|Epson|L3110||2020||1|
43|Comunicación|Sala de Reuniones|Impresora Sala de Reuniones|HP|LaserJet P1606dn||2014||1|
44|Dirección|Dirección|Impresora Proviseur|HP|LaserJet P1606dn||2022||1|Marca corregida: el inventario indicaba EPSON para un modelo LaserJet de HP.
45|Dirección|Subdirección Primaria|Impresora Subdirección Primaria|HP|LaserJet P1606dn||2014||1|
46|Dirección|Subdirección|Impresora Subdirección|HP|LaserJet P1606dn||2015||1|
47|Dirección|Secretaría de Dirección*|Impresora Secretaría de Dirección|HP|LaserJet P1606dn||2016||1|
48|Dirección|Hall*|Impresora Hall Dirección|Epson|L6171||2020||1|
49|Dirección|Dirección|Impresora Dirección|Epson|L3110||2020||1|
50|Dirección|Dirección Primaria|Impresora Dirección Primaria|Epson|L380||2018||2|
TABLA;

    /** @var string[] */
    private array $advertencias = [];

    /** @var string[] Ubicaciones creadas en esta ejecución. */
    private array $ubicacionesCreadas = [];

    private int $creados = 0;
    private int $actualizados = 0;
    private int $fichasRed = 0;

    public function run(): void
    {
        $filas = $this->leerInventario();
        $this->validar($filas);

        // Semilla fija: las fechas aleatorias son las mismas en cada ejecución.
        mt_srand(2026);

        DB::transaction(function () use ($filas) {
            $categoria = Categoria::find(self::ID_CATEGORIA);
            if (!$categoria || stripos($categoria->nombre_categoria, 'impresora') === false) {
                throw new RuntimeException('La categoría ' . self::ID_CATEGORIA . ' no existe o no es la de Impresoras.');
            }

            $ubicaciones = $this->resolverUbicaciones($filas);

            foreach ($filas as $fila) {
                $this->cargarEquipo($fila, $ubicaciones[$this->clave($fila)]);
            }
        });

        $this->advertenciasDeLote($filas);
        $this->resumen();
    }

    /** @return array<int, array<string, mixed>> */
    private function leerInventario(): array
    {
        $filas = [];

        foreach (preg_split('/\R/', trim(self::INVENTARIO)) as $linea) {
            $c = array_map('trim', explode('|', $linea));

            $esNueva = str_ends_with($c[2], '*');

            $filas[] = [
                'n' => (int) $c[0],
                'seccion' => $c[1],
                'aula' => $esNueva ? rtrim($c[2], '* ') : $c[2],
                'aula_nueva' => $esNueva,
                'nombre' => $c[3],
                'marca' => $c[4],
                'modelo' => $c[5],
                'codigo' => $c[6],
                'anio' => $c[7],
                'ip' => $c[8],
                'estado' => (int) $c[9],
                'nota' => $c[10] ?? '',
            ];
        }

        return $filas;
    }

    private function validar(array $filas): void
    {
        $conteo = array_count_values(array_column($filas, 'estado'));
        ksort($conteo);

        if (count($filas) !== 50 || $conteo !== self::CONTEO_ESTADOS) {
            throw new RuntimeException(
                'Inventario inconsistente: se esperaban 50 equipos con estados ' . json_encode(self::CONTEO_ESTADOS)
                . ' y se leyeron ' . count($filas) . ' con ' . json_encode($conteo) . '; no se cargó nada.'
            );
        }

        if (count(array_unique(array_column($filas, 'n'))) !== 50) {
            throw new RuntimeException('Hay Nº repetidos en el inventario; no se cargó nada.');
        }
    }

    private function clave(array $fila): string
    {
        return $fila['seccion'] . '|' . $fila['aula'];
    }

    /**
     * Busca cada ubicación por (sección, aula); sin aula, por sección con aula
     * NULL. Solo las marcadas con * se crean; si falta cualquier otra se detiene
     * antes de guardar nada.
     *
     * @return array<string, Ubicacion>
     */
    private function resolverUbicaciones(array $filas): array
    {
        $resueltas = [];
        $faltantes = [];

        foreach ($filas as $fila) {
            $clave = $this->clave($fila);
            if (isset($resueltas[$clave]) || isset($faltantes[$clave])) {
                continue;
            }

            $consulta = Ubicacion::where('seccion', $fila['seccion']);
            $fila['aula'] === '' ? $consulta->whereNull('aula') : $consulta->where('aula', $fila['aula']);
            $ubicacion = $consulta->first();

            if (!$ubicacion && $fila['aula_nueva']) {
                $ubicacion = Ubicacion::firstOrCreate(['seccion' => $fila['seccion'], 'aula' => $fila['aula']]);
                $this->ubicacionesCreadas[] = "{$fila['seccion']} / {$fila['aula']}";
            }

            if ($ubicacion) {
                $resueltas[$clave] = $ubicacion;
            } else {
                $faltantes[$clave] = "{$fila['seccion']} / " . ($fila['aula'] === '' ? '(sin aula)' : $fila['aula']) . " (Nº {$fila['n']})";
            }
        }

        if ($faltantes) {
            throw new RuntimeException(
                "Faltan ubicaciones en la tabla `ubicaciones`; no se cargó nada:\n  - " . implode("\n  - ", $faltantes)
            );
        }

        return $resueltas;
    }

    private function cargarEquipo(array $fila, Ubicacion $ubicacion): void
    {
        $estado = self::ESTADOS[$fila['estado']];

        $codigo = $fila['codigo'] !== ''
            ? 'IMP-' . $fila['codigo']
            : 'IMP-SC-' . str_pad((string) $fila['n'], 2, '0', STR_PAD_LEFT);

        $fecha = null;
        if ($fila['anio'] === '') {
            $this->advertencias[] = "Nº {$fila['n']} ({$fila['nombre']}): sin año de compra → fecha_adquisicion NULL.";
        } else {
            $fecha = Carbon::create((int) $fila['anio'], mt_rand(1, 12), mt_rand(1, 28))->startOfDay();
            if ($fecha->isFuture()) {
                $fecha = Carbon::today();
            }
        }

        $observaciones = array_filter([
            'Estado según inventario: ' . $estado['texto'] . '.',
            $fila['nota'],
        ]);

        $origen = 'IMPRESORAS|' . mb_strtoupper($fila['seccion']) . '|' . mb_strtoupper($fila['aula']) . '|' . $fila['n'];

        // origen_externo no está en $fillable del modelo: se asigna sin protección de asignación masiva.
        $activo = ActivoTecnologico::unguarded(fn () => ActivoTecnologico::updateOrCreate(
            ['codigo_patrimonial' => $codigo],
            [
                'fk_id_categoria' => self::ID_CATEGORIA,
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'origen_externo' => $origen,
                'nombre_equipo' => $fila['nombre'],
                'marca' => $fila['marca'],
                'modelo' => $fila['modelo'],
                'fecha_adquisicion' => $fecha?->toDateString(),
                'estado_operativo' => 'Operativo',
                'etapa_ciclo_vida' => $estado['etapa'],
                'observaciones' => implode(' ', $observaciones),
                'nivel_riesgo' => null,
            ]
        ));

        $activo->wasRecentlyCreated ? $this->creados++ : $this->actualizados++;

        // La ficha de red solo existe para las impresoras que tienen IP.
        if ($fila['ip'] !== '') {
            DetalleEquipoRed::updateOrCreate(
                ['fk_id_activo' => $activo->id_activo],
                ['direccion_ip' => $fila['ip'], 'version_firmware' => null]
            );
            $this->fichasRed++;
        }
    }

    /** Advertencias que se deducen del conjunto de datos. */
    private function advertenciasDeLote(array $filas): void
    {
        $provisionales = count(array_filter($filas, fn ($f) => $f['codigo'] === ''));
        $conCodigo = count($filas) - $provisionales;

        $advertencias = [
            "{$provisionales} equipos con código provisional IMP-SC-xx (solo {$conCodigo} tienen código en el inventario).",
        ];

        foreach ($filas as $fila) {
            $nota = $fila['nota'];

            if (stripos($nota, 'Estado vacío') !== false) {
                $advertencias[] = "Nº {$fila['n']}: estado asumido como Buen funcionamiento (vacío en el inventario).";
            }
            if (stripos($nota, 'escáner') !== false) {
                $advertencias[] = "Nº {$fila['n']}: es un escáner registrado en la categoría Impresoras.";
            }
            if (stripos($nota, 'ubicación provisional') !== false) {
                $advertencias[] = "Nº {$fila['n']}: ubicación provisional {$fila['aula']} (sin aula en el inventario).";
            }
            if (stripos($nota, 'Marca corregida') !== false) {
                $advertencias[] = "Nº {$fila['n']}: marca corregida de EPSON a {$fila['marca']}.";
            }
        }

        $advertencias[] = $this->ubicacionesCreadas
            ? 'Ubicaciones creadas (' . count($this->ubicacionesCreadas) . '): ' . implode('; ', $this->ubicacionesCreadas) . '.'
            : 'No se creó ninguna ubicación nueva.';

        $this->advertencias = array_merge($advertencias, $this->advertencias);
    }

    private function resumen(): void
    {
        $consola = $this->command;

        $consola->newLine();
        $consola->info('Carga de impresoras finalizada');
        $consola->table(['Concepto', 'Cantidad'], [
            ['Equipos en la categoría ' . self::ID_CATEGORIA, ActivoTecnologico::where('fk_id_categoria', self::ID_CATEGORIA)->count()],
            ['Creados en esta ejecución', $this->creados],
            ['Actualizados (ya existían)', $this->actualizados],
            ['Ubicaciones creadas', count($this->ubicacionesCreadas)],
            ['Fichas de red con IP', $this->fichasRed],
        ]);

        $consola->warn('Advertencias (' . count($this->advertencias) . '):');
        foreach ($this->advertencias as $advertencia) {
            $consola->line('  - ' . $advertencia);
        }

        $consola->newLine();
        $consola->info('Verificación: activos de la categoría ' . self::ID_CATEGORIA);
        $consola->table(
            ['codigo_patrimonial', 'nombre_equipo', 'seccion', 'aula', 'marca', 'modelo', 'fecha_adquisicion', 'etapa_ciclo_vida', 'direccion_ip'],
            DB::table('activos_tecnologicos as a')
                ->join('ubicaciones as u', 'u.id_ubicacion', '=', 'a.fk_id_ubicacion')
                ->leftJoin('detalles_equipo_red as d', 'd.fk_id_activo', '=', 'a.id_activo')
                ->where('a.fk_id_categoria', self::ID_CATEGORIA)
                ->orderBy('a.id_activo')
                ->get(['a.codigo_patrimonial', 'a.nombre_equipo', 'u.seccion', 'u.aula', 'a.marca', 'a.modelo', 'a.fecha_adquisicion', 'a.etapa_ciclo_vida', 'd.direccion_ip'])
                ->map(fn ($f) => array_map(fn ($v) => $v ?? '—', (array) $f))
                ->all()
        );
    }
}
