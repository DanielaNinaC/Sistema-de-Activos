<?php

namespace Database\Seeders;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\DetalleEquipoRed;
use App\Models\Ubicacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Carga el inventario CCTV (4 grabadores NVR y 101 cámaras) en la categoría 10.
 *
 *   php artisan db:seed --class=CamarasCctvSeeder
 *
 * Atómico (todo o nada) e idempotente: la clave es codigo_patrimonial en
 * activos_tecnologicos y fk_id_activo en detalles_equipo_red.
 * No ejecuta evaluaciones de riesgo (nivel_riesgo queda NULL).
 */
class CamarasCctvSeeder extends Seeder
{
    private const ID_CATEGORIA = 10;
    private const SECCION_PROVISIONAL = 'Seguridad';

    private const TIPOS = [
        'NVR' => ['nombre' => 'NVR', 'observacion' => 'Grabador de video en red (NVR)'],
        'IPC' => ['nombre' => 'Cámara IP', 'observacion' => 'Cámara IP (IPC)'],
        'SD' => ['nombre' => 'Cámara Domo PTZ', 'observacion' => 'Cámara domo PTZ (Speed Dome)'],
    ];

    /** Modelos que el inventario trae como texto genérico: no identifican un modelo real. */
    private const MODELOS_GENERICOS = ['NVR', 'HIKVISION'];

    /** Grabadores NVR: Nº => [marca, modelo, versión]. */
    private const NVR = [
        1 => ['DAHUA', 'DHI-NVR4432-4KS2/I', '4.001.0000001.4.R'],
        2 => ['DAHUA', 'DHI-NVR4432-4KS2/I', '4.001.0000001.4.R'],
        3 => ['DAHUA', 'DHI-NVR5432-4KS2(V2.0)', '4.002.0000000.6.R'],
        4 => ['DAHUA', 'NVR', '3.205.0000000.0'],
    ];

    /** Cámaras DAHUA por rango de Nº: [desde, hasta, tipo, modelo, versión]. */
    private const RANGOS = [
        [1, 9, 'IPC', 'DH-IPC-HDBW1431EN-S4', '2.820.0000000.49.R'],
        [10, 17, 'IPC', 'DH-IPC-HDW1230T1-S5', '2.800.0000000.7.R'],
        [18, 18, 'IPC', 'DH-IPC-HDW1431T1N-S4', '2.820.0000000.48.R'],
        [19, 28, 'SD', 'DH-SD6CE445XAN-HNR', '2.812.0000005.4.R'],
        [29, 34, 'IPC', 'IPC-B1A30', '2.622.0000000.31.R'],
        [35, 38, 'IPC', 'IPC-D1A30', '2.622.0000000.31.R'],
        [39, 41, 'IPC', 'IPC-EB5531', '2.622.0000000.31.R'],
        [42, 49, 'IPC', 'IPC-HDBW1220E', '2.420.0.4'],
        [50, 59, 'IPC', 'IPC-HDBW4421E-AS', '2.400.0.23'],
        [60, 62, 'IPC', 'IPC-HDW1420S', '2.400.0.30'],
        [63, 67, 'IPC', 'IPC-HDW1431T1-S4', '2.800.0000000.19.R'],
        [68, 70, 'IPC', 'IPC-HFW1220S', '2.420.0.4'],
        [71, 71, 'IPC', 'IPC-HFW1320S', '2.400.0.23'],
        [72, 78, 'IPC', 'IPC-HFW1431S', '2.800.0000008.0.R'],
        [79, 79, 'IPC', 'IPC-HFW1831E', '2.622.0000000.18.R'],
        [80, 90, 'IPC', 'IPC-HFW4421B', '2.400.0.23'],
        [91, 93, 'IPC', 'IPC-HFW4421S', '2.400.0.23'],
        [94, 100, 'IPC', 'IPC-T1B40', '2.622.0000000.5.R'],
    ];

    /** Excepciones de versión dentro de un rango: Nº de cámara => versión. */
    private const VERSIONES_EXCEPCION = [
        12 => '2.800.0000000.3.R',
        22 => '2.812.0000032.2.R',
    ];

    /** Fecha de adquisición por posición en la lista (1 a 105). */
    private const FECHAS = [
        [1, 35, '2023-03-15'],
        [36, 70, '2023-07-12'],
        [71, 105, '2023-10-20'],
    ];

    /** @var string[] */
    private array $advertencias = [];

    private int $creados = 0;
    private int $actualizados = 0;

    public function run(): void
    {
        $equipos = $this->construirEquipos();

        DB::transaction(function () use ($equipos) {
            $categoria = Categoria::find(self::ID_CATEGORIA);
            if (!$categoria || stripos($categoria->nombre_categoria, 'cámara') === false) {
                throw new RuntimeException('La categoría ' . self::ID_CATEGORIA . ' no existe o no es la de Cámaras CCTV.');
            }

            $ubicacion = Ubicacion::where('seccion', self::SECCION_PROVISIONAL)->whereNull('aula')->first()
                ?? Ubicacion::where('seccion', self::SECCION_PROVISIONAL)->first();

            if (!$ubicacion) {
                throw new RuntimeException("No existe la ubicación provisional '" . self::SECCION_PROVISIONAL . "' en la tabla `ubicaciones`; no se cargó nada.");
            }

            foreach ($equipos as $equipo) {
                $this->cargarEquipo($equipo, $ubicacion);
            }
        });

        $this->resumen();
    }

    /**
     * Expande los rangos a un registro por equipo (105 en total) y valida el
     * inventario completo antes de insertar.
     *
     * @return array<int, array<string, mixed>>
     */
    private function construirEquipos(): array
    {
        $equipos = [];

        foreach (self::NVR as $n => [$marca, $modelo, $version]) {
            $equipos[] = ['clase' => 'NVR', 'n' => $n, 'tipo' => 'NVR', 'marca' => $marca, 'modelo' => $modelo, 'version' => $version];
        }

        foreach (self::RANGOS as [$desde, $hasta, $tipo, $modelo, $version]) {
            for ($n = $desde; $n <= $hasta; $n++) {
                $equipos[] = [
                    'clase' => 'CAMARA', 'n' => $n, 'tipo' => $tipo, 'marca' => 'DAHUA', 'modelo' => $modelo,
                    'version' => self::VERSIONES_EXCEPCION[$n] ?? $version,
                ];
            }
        }

        // Cámara 101: marca HIKVISION con modelo genérico y sin versión.
        $equipos[] = ['clase' => 'CAMARA', 'n' => 101, 'tipo' => 'IPC', 'marca' => 'HIKVISION', 'modelo' => 'HIKVISION', 'version' => null];

        $this->validarInventario($equipos);

        // La fecha depende de la posición en la lista (NVR primero, luego cámaras).
        foreach ($equipos as $i => &$equipo) {
            $equipo['posicion'] = $i + 1;
            $equipo['fecha'] = $this->fechaPorPosicion($i + 1);
        }
        unset($equipo);

        return $equipos;
    }

    private function validarInventario(array $equipos): void
    {
        $porTipo = array_count_values(array_column($equipos, 'tipo'));
        $esperado = ['NVR' => 4, 'IPC' => 91, 'SD' => 10];
        ksort($porTipo);
        ksort($esperado);

        if (count($equipos) !== 105 || $porTipo !== $esperado) {
            throw new RuntimeException(
                'Inventario inconsistente: se esperaban 105 equipos (4 NVR, 91 IPC, 10 SD) y se armaron '
                . count($equipos) . ' (' . json_encode($porTipo) . '); no se cargó nada.'
            );
        }

        $camaras = array_column(array_filter($equipos, fn ($e) => $e['clase'] === 'CAMARA'), 'n');
        sort($camaras);

        if ($camaras !== range(1, 101)) {
            throw new RuntimeException('Los rangos de cámaras no cubren exactamente los Nº 1 a 101 sin huecos ni repetidos; no se cargó nada.');
        }
    }

    private function fechaPorPosicion(int $posicion): string
    {
        foreach (self::FECHAS as [$desde, $hasta, $fecha]) {
            if ($posicion >= $desde && $posicion <= $hasta) {
                return $fecha;
            }
        }

        throw new RuntimeException("Sin fecha de adquisición definida para la posición {$posicion}.");
    }

    private function cargarEquipo(array $e, Ubicacion $ubicacion): void
    {
        $esNvr = $e['clase'] === 'NVR';
        $tipo = self::TIPOS[$e['tipo']];
        $etiqueta = ($esNvr ? 'NVR ' : 'Cámara ') . $e['n'];

        $codigo = $esNvr
            ? 'NVR-' . str_pad((string) $e['n'], 2, '0', STR_PAD_LEFT)
            : 'CAM-' . str_pad((string) $e['n'], 4, '0', STR_PAD_LEFT);

        $origen = 'CCTV|SEGURIDAD|' . ($esNvr ? 'NVR' : 'CAMARA') . '|' . $e['n'];

        // Si el equipo ya se cargó con otro formato de código (p. ej. CAM-001), se
        // renombra en vez de crear uno nuevo: origen_externo es la identidad estable.
        ActivoTecnologico::where('origen_externo', $origen)
            ->where('codigo_patrimonial', '<>', $codigo)
            ->update(['codigo_patrimonial' => $codigo]);

        $observaciones = ['Tipo: ' . $tipo['observacion'] . '.'];

        // Un modelo genérico no identifica el equipo: se guarda NULL y se avisa.
        $modelo = $e['modelo'];
        if (in_array(mb_strtoupper($modelo), self::MODELOS_GENERICOS, true)) {
            $modelo = null;
            $observaciones[] = 'Modelo no especificado en el inventario.';
            $this->advertencias[] = "{$etiqueta}: modelo no especificado en el inventario (venía como \"{$e['modelo']}\").";
        }

        $version = $e['version'];
        if ($version === null || $version === '') {
            $version = null;
            $observaciones[] = 'Versión de firmware no registrada en el inventario.';
            $this->advertencias[] = "{$etiqueta}: versión de firmware no especificada.";
        }

        // origen_externo no está en $fillable del modelo: se asigna sin protección de asignación masiva.
        $activo = ActivoTecnologico::unguarded(fn () => ActivoTecnologico::updateOrCreate(
            ['codigo_patrimonial' => $codigo],
            [
                'fk_id_categoria' => self::ID_CATEGORIA,
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'origen_externo' => $origen,
                'nombre_equipo' => $tipo['nombre'] . ' ' . $e['n'],
                'marca' => ucfirst(mb_strtolower($e['marca'])),
                'modelo' => $modelo,
                'fecha_adquisicion' => $e['fecha'],
                'estado_operativo' => 'Operativo',
                'etapa_ciclo_vida' => 'Operación',
                'observaciones' => implode(' ', $observaciones),
                'nivel_riesgo' => null,
            ]
        ));

        $activo->wasRecentlyCreated ? $this->creados++ : $this->actualizados++;

        DetalleEquipoRed::updateOrCreate(
            ['fk_id_activo' => $activo->id_activo],
            ['direccion_ip' => null, 'version_firmware' => $version]
        );
    }

    private function resumen(): void
    {
        $consola = $this->command;

        // Advertencias generales, siempre presentes.
        $generales = [
            "Todos los equipos quedaron en la ubicación provisional '" . self::SECCION_PROVISIONAL . "': hay que reasignarlos a su ubicación real.",
            'Los códigos NVR-xx y CAM-xxxx son provisionales (el inventario no trae código patrimonial).',
        ];
        $advertencias = array_merge($generales, $this->advertencias);

        $consola->newLine();
        $consola->info('Carga de cámaras CCTV y NVR finalizada');
        $consola->table(['Concepto', 'Cantidad'], [
            ['Equipos en la categoría ' . self::ID_CATEGORIA, ActivoTecnologico::where('fk_id_categoria', self::ID_CATEGORIA)->count()],
            ['Creados en esta ejecución', $this->creados],
            ['Actualizados (ya existían)', $this->actualizados],
        ]);

        $consola->info('Equipos por fecha de adquisición:');
        $consola->table(
            ['fecha_adquisicion', 'equipos'],
            ActivoTecnologico::where('fk_id_categoria', self::ID_CATEGORIA)
                ->selectRaw('fecha_adquisicion, COUNT(*) as total')
                ->groupBy('fecha_adquisicion')
                ->orderBy('fecha_adquisicion')
                ->get()
                ->map(fn ($f) => [(string) $f->fecha_adquisicion, $f->total])
                ->all()
        );

        $consola->warn('Advertencias (' . count($advertencias) . '):');
        foreach ($advertencias as $advertencia) {
            $consola->line('  - ' . $advertencia);
        }

        $consola->newLine();
        $consola->info('Verificación: activos de la categoría ' . self::ID_CATEGORIA);
        $consola->table(
            ['codigo_patrimonial', 'nombre_equipo', 'marca', 'modelo', 'fecha_adquisicion', 'version_firmware'],
            DB::table('activos_tecnologicos as a')
                ->leftJoin('detalles_equipo_red as d', 'd.fk_id_activo', '=', 'a.id_activo')
                ->where('a.fk_id_categoria', self::ID_CATEGORIA)
                ->orderBy('a.id_activo')
                ->get(['a.codigo_patrimonial', 'a.nombre_equipo', 'a.marca', 'a.modelo', 'a.fecha_adquisicion', 'd.version_firmware'])
                ->map(fn ($f) => array_map(fn ($v) => $v ?? '—', (array) $f))
                ->all()
        );
    }
}
