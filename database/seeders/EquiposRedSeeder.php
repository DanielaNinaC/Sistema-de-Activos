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
 * Carga los equipos de red: 1 router (cat. 2), 20 switches (cat. 3) y
 * 34 puntos de acceso (cat. 4).
 *
 *   php artisan db:seed --class=EquiposRedSeeder
 *
 * Atómico (todo o nada) e idempotente: la clave es codigo_patrimonial en
 * activos_tecnologicos y fk_id_activo en detalles_equipo_red. No modifica ni
 * borra registros previos de esas categorías con otros códigos (solo los lista)
 * y no ejecuta evaluaciones de riesgo (nivel_riesgo queda NULL).
 */
class EquiposRedSeeder extends Seeder
{
    /** categoría => [palabra esperada en su nombre, etiqueta para origen_externo, conteos esperados por estado] */
    private const CATEGORIAS = [
        2 => ['nombre' => 'router', 'tipo' => 'ROUTER', 'total' => 1, 'estados' => [1 => 1, 2 => 0, 3 => 0]],
        3 => ['nombre' => 'switch', 'tipo' => 'SWITCH', 'total' => 20, 'estados' => [1 => 16, 2 => 3, 3 => 1]],
        4 => ['nombre' => 'acceso', 'tipo' => 'AP', 'total' => 34, 'estados' => [1 => 0, 2 => 28, 3 => 6]],
    ];

    private const ESTADOS = [
        1 => ['etapa' => 'Operación', 'texto' => 'Buen funcionamiento'],
        2 => ['etapa' => 'Mantenimiento', 'texto' => 'Equipo antiguo, se sugiere mantenimiento o actualización'],
        3 => ['etapa' => 'Renovación / Retiro', 'texto' => 'Obsoleto, se sugiere cambio de equipo'],
    ];

    private const NOTA_FINAL = 'Datos técnicos referenciales (modelo, IP, firmware, fecha y ubicación); verificar con el inventario físico.';

    /** Código|Cat.|Nombre equipo|Marca|Modelo|Sección|Aula|Fecha adquisición|IP gestión|Firmware|Estado|Nota   ("—" = sin dato) */
    private const INVENTARIO = <<<'TABLA'
RTR-01|2|Router Principal|MikroTik|RB4011iGS+RM|Informática|Sarah|2022-03-14|130.130.1.1|RouterOS 7.14.3|1|Router de borde y enrutamiento entre VLAN.
SWT-01|3|Switch Core|Cisco|CBS350-24P-4G|Informática|Sarah|2022-03-14|130.130.1.11|3.2.1.1|1|Switch núcleo de la red.
SWT-02|3|Switch Distribución Informática|Cisco|CBS350-24P-4G|Informática|Sarah|2022-03-14|130.130.1.12|3.2.1.1|1|
SWT-03|3|Switch Maternal P5|TP-Link|TL-SG3428|Maternal|P5|2021-08-09|130.130.1.13|1.0.12|1|
SWT-04|3|Switch Sala de Profesores Maternal|TP-Link|TL-SG2210P|Maternal|Sala de Profesores Maternal|2023-02-20|130.130.1.14|1.0.4|1|
SWT-05|3|Switch BCD 10|TP-Link|TL-SG2210P|BCD|10|2023-02-20|130.130.1.15|1.0.4|1|
SWT-06|3|Switch Primaria 19|TP-Link|TL-SG3428|Primaria|19|2021-08-09|130.130.1.16|1.0.12|1|
SWT-07|3|Switch Primaria 25|TP-Link|TL-SG3428|Primaria|25|2021-08-09|130.130.1.17|1.0.12|1|
SWT-08|3|Switch Primaria 30|TP-Link|TL-SG3428|Primaria|30|2024-05-06|130.130.1.18|1.0.12|1|
SWT-09|3|Switch Primaria S3|TP-Link|TL-SG2210P|Primaria|S3|2024-05-06|130.130.1.19|1.0.4|1|
SWT-10|3|Switch Secundaria 9|TP-Link|TL-SG3428|Secundaria|9|2020-07-15|130.130.1.20|1.0.12|1|
SWT-11|3|Switch Secundaria 14|TP-Link|TL-SG3428|Secundaria|14|2020-07-15|130.130.1.21|1.0.12|1|
SWT-12|3|Switch Secundaria 20|TP-Link|TL-SG3428|Secundaria|20|2020-07-15|130.130.1.22|1.0.12|1|
SWT-13|3|Switch Secundaria B03|TP-Link|TL-SG2210P|Secundaria|B03|2023-02-20|130.130.1.23|1.0.4|1|
SWT-14|3|Switch CDI|Cisco|CBS250-24T-4G|Secundaria|CDI|2022-03-14|130.130.1.24|3.2.0.84|1|
SWT-15|3|Switch Laboratorio EXAO|Cisco|CBS250-24T-4G|Secundaria|Laboratorio EXAO|2022-03-14|130.130.1.25|3.2.0.84|1|
SWT-16|3|Switch Administración DAF|Cisco|CBS250-24T-4G|Administración|DAF|2022-03-14|130.130.1.26|3.2.0.84|1|
SWT-17|3|Switch Dirección|HP|1920-24G (JG924A)|Dirección|Dirección|2018-04-23|130.130.1.27|5.20.99 R1118|2|
SWT-18|3|Switch Teatro|HP|1920-24G (JG924A)|Comunicación|Teatro|2018-04-23|130.130.1.28|5.20.99 R1118|2|
SWT-19|3|Switch Secundaria A02|Cisco|SG220-26|Secundaria|A02|2018-04-23|130.130.1.29|1.1.4.1|2|
SWT-20|3|Switch Laboratorio de Ciencias|TP-Link|TL-SG1024|Secundaria|Lab. Ciencias|2014-06-10|—|—|3|Switch no administrable (sin IP de gestión ni firmware actualizable).
AP-01|4|AP Maternal P2|TP-Link|TL-WA801ND|Maternal|P2|2014-05-12|130.130.2.11|3.16.9 Build 150310|3|Estándar Wi-Fi 4 (802.11n); sin soporte del fabricante.
AP-02|4|AP Maternal P4|TP-Link|TL-WA801ND|Maternal|P4|2014-05-12|130.130.2.12|3.16.9 Build 150310|3|Estándar Wi-Fi 4 (802.11n); sin soporte del fabricante.
AP-03|4|AP Primaria 19|TP-Link|TL-WA801ND|Primaria|19|2015-03-02|130.130.2.13|3.16.9 Build 150310|3|Estándar Wi-Fi 4 (802.11n); sin soporte del fabricante.
AP-04|4|AP Primaria 21|TP-Link|TL-WA801ND|Primaria|21|2015-03-02|130.130.2.14|3.16.9 Build 150310|3|Estándar Wi-Fi 4 (802.11n); sin soporte del fabricante.
AP-05|4|AP Secundaria 7|TP-Link|TL-WA801ND|Secundaria|7|2015-03-02|130.130.2.15|3.16.9 Build 150310|3|Estándar Wi-Fi 4 (802.11n); sin soporte del fabricante.
AP-06|4|AP Secundaria 9|TP-Link|TL-WA801ND|Secundaria|9|2015-03-02|130.130.2.16|3.16.9 Build 150310|3|Estándar Wi-Fi 4 (802.11n); sin soporte del fabricante.
AP-07|4|AP Maternal P6|Ubiquiti|UAP-AC-LR|Maternal|P6|2018-02-19|130.130.2.17|6.5.62|2|
AP-08|4|AP Maternal P9|Ubiquiti|UAP-AC-LR|Maternal|P9|2018-02-19|130.130.2.18|6.5.62|2|
AP-09|4|AP Maternal P12|Ubiquiti|UAP-AC-LR|Maternal|P12|2018-02-19|130.130.2.19|6.5.62|2|
AP-10|4|AP BCD 10|Ubiquiti|UAP-AC-LR|BCD|10|2018-02-19|130.130.2.20|6.5.62|2|
AP-11|4|AP Primaria 23|Ubiquiti|UAP-AC-LR|Primaria|23|2018-02-19|130.130.2.21|6.5.62|2|
AP-12|4|AP Primaria 25|Ubiquiti|UAP-AC-LR|Primaria|25|2018-02-19|130.130.2.22|6.5.62|2|
AP-13|4|AP Primaria 27|Ubiquiti|UAP-AC-LR|Primaria|27|2018-02-19|130.130.2.23|6.5.62|2|
AP-14|4|AP Primaria 29|Ubiquiti|UAP-AC-LR|Primaria|29|2018-02-19|130.130.2.24|6.5.62|2|
AP-15|4|AP Primaria 31|Ubiquiti|UAP-AC-LR|Primaria|31|2018-02-19|130.130.2.25|6.5.62|2|
AP-16|4|AP Primaria 33|Ubiquiti|UAP-AC-LR|Primaria|33|2018-02-19|130.130.2.26|6.5.62|2|
AP-17|4|AP Primaria S1|Ubiquiti|UAP-AC-LR|Primaria|S1|2018-02-19|130.130.2.27|6.5.62|2|
AP-18|4|AP Primaria S3|Ubiquiti|UAP-AC-LR|Primaria|S3|2018-02-19|130.130.2.28|6.5.62|2|
AP-19|4|AP Primaria S5|Ubiquiti|UAP-AC-LR|Primaria|S5|2018-02-19|130.130.2.29|6.5.62|2|
AP-20|4|AP Bloques de Primaria|Ubiquiti|UAP-AC-LR|Primaria|Bloques de Primaria|2018-02-19|130.130.2.30|6.5.62|2|
AP-21|4|AP Secundaria 11|Ubiquiti|UAP-AC-LR|Secundaria|11|2019-01-21|130.130.2.31|6.5.62|2|
AP-22|4|AP Secundaria 13|Ubiquiti|UAP-AC-LR|Secundaria|13|2019-01-21|130.130.2.32|6.5.62|2|
AP-23|4|AP Secundaria 15|Ubiquiti|UAP-AC-LR|Secundaria|15|2019-01-21|130.130.2.33|6.5.62|2|
AP-24|4|AP Secundaria 17|Ubiquiti|UAP-AC-LR|Secundaria|17|2019-01-21|130.130.2.34|6.5.62|2|
AP-25|4|AP Secundaria 20|Ubiquiti|UAP-AC-LR|Secundaria|20|2019-01-21|130.130.2.35|6.5.62|2|
AP-26|4|AP Secundaria 23|Ubiquiti|UAP-AC-LR|Secundaria|23|2019-01-21|130.130.2.36|6.5.62|2|
AP-27|4|AP Secundaria 26|Ubiquiti|UAP-AC-LR|Secundaria|26|2019-01-21|130.130.2.37|6.5.62|2|
AP-28|4|AP Secundaria A01|Ubiquiti|UAP-AC-LR|Secundaria|A01|2019-01-21|130.130.2.38|6.5.62|2|
AP-29|4|AP Secundaria B01|Ubiquiti|UAP-AC-LR|Secundaria|B01|2019-01-21|130.130.2.39|6.5.62|2|
AP-30|4|AP Secundaria B04|Ubiquiti|UAP-AC-LR|Secundaria|B04|2019-01-21|130.130.2.40|6.5.62|2|
AP-31|4|AP CDI|Ubiquiti|UAP-AC-LR|Secundaria|CDI|2019-01-21|130.130.2.41|6.5.62|2|
AP-32|4|AP Lab. Ciencias|Ubiquiti|UAP-AC-LR|Secundaria|Lab. Ciencias|2019-01-21|130.130.2.42|6.5.62|2|
AP-33|4|AP Artes Plásticas|Ubiquiti|UAP-AC-LR|Secundaria|Artes Plásticas|2019-01-21|130.130.2.43|6.5.62|2|
AP-34|4|AP Teatro|Ubiquiti|UAP-AC-LR|Comunicación|Teatro|2019-01-21|130.130.2.44|6.5.62|2|
TABLA;

    /** @var string[] */
    private array $advertencias = [];

    private int $creados = 0;
    private int $actualizados = 0;

    public function run(): void
    {
        $filas = $this->leerInventario();
        $this->validar($filas);

        DB::transaction(function () use ($filas) {
            $this->validarCategorias();
            $ubicaciones = $this->resolverUbicaciones($filas);

            foreach ($filas as $fila) {
                $this->cargarEquipo($fila, $ubicaciones[$fila['seccion'] . '|' . $fila['aula']]);
            }
        });

        $this->advertenciasDeLote($filas);
        $this->resumen($filas);
    }

    /** @return array<int, array<string, mixed>> */
    private function leerInventario(): array
    {
        $dato = fn (string $v) => ($v === '—' || $v === '') ? null : $v;
        $filas = [];

        foreach (preg_split('/\R/', trim(self::INVENTARIO)) as $linea) {
            $c = array_map('trim', explode('|', $linea));

            $filas[] = [
                'codigo' => $c[0],
                'categoria' => (int) $c[1],
                'nombre' => $c[2],
                'marca' => $c[3],
                'modelo' => $c[4],
                'seccion' => $c[5],
                'aula' => $c[6],
                'fecha' => $c[7],
                'ip' => $dato($c[8]),
                'firmware' => $dato($c[9]),
                'estado' => (int) $c[10],
                'nota' => $c[11] ?? '',
            ];
        }

        return $filas;
    }

    /** Si el inventario no cuadra con los conteos esperados, no se guarda nada. */
    private function validar(array $filas): void
    {
        $errores = [];

        foreach (self::CATEGORIAS as $id => $esperado) {
            $deLaCategoria = array_filter($filas, fn ($f) => $f['categoria'] === $id);
            $conteo = array_count_values(array_column($deLaCategoria, 'estado'));

            $real = [1 => $conteo[1] ?? 0, 2 => $conteo[2] ?? 0, 3 => $conteo[3] ?? 0];

            if (count($deLaCategoria) !== $esperado['total'] || $real !== $esperado['estados']) {
                $errores[] = "Categoría {$id} ({$esperado['tipo']}): se esperaban {$esperado['total']} equipos con estados "
                    . json_encode($esperado['estados']) . ' y hay ' . count($deLaCategoria) . ' con ' . json_encode($real);
            }
        }

        if (count($filas) !== 55) {
            $errores[] = 'Se esperaban 55 equipos y se leyeron ' . count($filas);
        }

        if (count(array_unique(array_column($filas, 'codigo'))) !== count($filas)) {
            $errores[] = 'Hay códigos repetidos en el inventario';
        }

        foreach ($filas as $f) {
            if (!isset(self::CATEGORIAS[$f['categoria']]) || !isset(self::ESTADOS[$f['estado']])) {
                $errores[] = "{$f['codigo']}: categoría o estado no reconocido";
            }
            if (strtotime($f['fecha']) === false || $f['fecha'] > now()->toDateString()) {
                $errores[] = "{$f['codigo']}: fecha de adquisición inválida o futura ({$f['fecha']})";
            }
        }

        if ($errores) {
            throw new RuntimeException("Inventario inconsistente; no se guardó nada:\n  - " . implode("\n  - ", $errores));
        }
    }

    private function validarCategorias(): void
    {
        foreach (self::CATEGORIAS as $id => $esperado) {
            $categoria = Categoria::find($id);

            if (!$categoria || stripos($categoria->nombre_categoria, $esperado['nombre']) === false) {
                throw new RuntimeException("La categoría {$id} no existe o no es la de {$esperado['tipo']}; no se guardó nada.");
            }
        }
    }

    /**
     * Busca cada ubicación por (sección, aula); si falta alguna se detiene,
     * informando todas las que faltan.
     *
     * @return array<string, Ubicacion>
     */
    private function resolverUbicaciones(array $filas): array
    {
        $resueltas = [];
        $faltantes = [];

        foreach ($filas as $f) {
            $clave = $f['seccion'] . '|' . $f['aula'];
            if (isset($resueltas[$clave]) || isset($faltantes[$clave])) {
                continue;
            }

            $ubicacion = Ubicacion::where('seccion', $f['seccion'])->where('aula', $f['aula'])->first();

            $ubicacion
                ? $resueltas[$clave] = $ubicacion
                : $faltantes[$clave] = "{$f['seccion']} / {$f['aula']} ({$f['codigo']})";
        }

        if ($faltantes) {
            throw new RuntimeException(
                "Faltan ubicaciones en la tabla `ubicaciones`; no se guardó nada:\n  - " . implode("\n  - ", $faltantes)
            );
        }

        return $resueltas;
    }

    private function cargarEquipo(array $f, Ubicacion $ubicacion): void
    {
        $estado = self::ESTADOS[$f['estado']];

        $observaciones = array_filter([
            'Estado: ' . $estado['texto'] . '.',
            $f['nota'],
            self::NOTA_FINAL,
        ]);

        // origen_externo no está en $fillable del modelo: se asigna sin protección de asignación masiva.
        $activo = ActivoTecnologico::unguarded(fn () => ActivoTecnologico::updateOrCreate(
            ['codigo_patrimonial' => $f['codigo']],
            [
                'fk_id_categoria' => $f['categoria'],
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'origen_externo' => 'RED|' . self::CATEGORIAS[$f['categoria']]['tipo'] . '|' . $f['codigo'],
                'nombre_equipo' => $f['nombre'],
                'marca' => $f['marca'],
                'modelo' => $f['modelo'],
                'fecha_adquisicion' => $f['fecha'],
                'estado_operativo' => 'Operativo',
                'etapa_ciclo_vida' => $estado['etapa'],
                'observaciones' => implode(' ', $observaciones),
                'nivel_riesgo' => null,
            ]
        ));

        $activo->wasRecentlyCreated ? $this->creados++ : $this->actualizados++;

        DetalleEquipoRed::updateOrCreate(
            ['fk_id_activo' => $activo->id_activo],
            ['direccion_ip' => $f['ip'], 'version_firmware' => $f['firmware']]
        );
    }

    /** Advertencias que se deducen de los datos. */
    private function advertenciasDeLote(array $filas): void
    {
        $sinIp = array_column(array_filter($filas, fn ($f) => $f['ip'] === null), 'codigo');
        $sinFirmware = array_column(array_filter($filas, fn ($f) => $f['firmware'] === null), 'codigo');

        if ($sinIp) {
            $this->advertencias[] = 'Sin IP de gestión: ' . implode(', ', $sinIp) . '.';
        }
        if ($sinFirmware) {
            $this->advertencias[] = 'Sin firmware: ' . implode(', ', $sinFirmware) . '.';
        }

        $ips = array_filter(array_column($filas, 'ip'));
        foreach (array_keys(array_filter(array_count_values($ips), fn ($n) => $n > 1)) as $ip) {
            $this->advertencias[] = "IP repetida en el inventario: {$ip}.";
        }

        $obsoletos = array_filter($filas, fn ($f) => $f['estado'] === 3);
        $this->advertencias[] = count($obsoletos) . ' equipos obsoletos (estado 3, "Renovación / Retiro"): '
            . implode(', ', array_column($obsoletos, 'codigo')) . '.';

        $this->advertencias[] = 'Datos técnicos referenciales: verificar con el inventario físico (así consta en las observaciones de cada equipo).';
    }

    private function resumen(array $filas): void
    {
        $c = $this->command;
        $codigos = array_column($filas, 'codigo');

        $c->newLine();
        $c->info('Carga de equipos de red finalizada');
        $c->table(['Concepto', 'Cantidad'], [
            ['Equipos del inventario', count($filas)],
            ['Creados', $this->creados],
            ['Actualizados (ya existían)', $this->actualizados],
        ]);

        // Conteo por categoría y estado (el estado 1/2/3 se refleja en la etapa del ciclo de vida).
        $etapa = fn ($e) => self::ESTADOS[$e]['etapa'];
        $filasCat = [];
        foreach (self::CATEGORIAS as $id => $esperado) {
            $base = ActivoTecnologico::where('fk_id_categoria', $id)->whereIn('codigo_patrimonial', $codigos);
            $filasCat[] = [
                $esperado['tipo'] . " (cat. {$id})",
                (clone $base)->count(),
                (clone $base)->where('etapa_ciclo_vida', $etapa(1))->count(),
                (clone $base)->where('etapa_ciclo_vida', $etapa(2))->count(),
                (clone $base)->where('etapa_ciclo_vida', $etapa(3))->count(),
                implode(' / ', $esperado['estados']),
            ];
        }
        $c->info('Por categoría y estado (1 = Operación, 2 = Mantenimiento, 3 = Renovación / Retiro)');
        $c->table(['Categoría', 'Equipos', 'Estado 1', 'Estado 2', 'Estado 3', 'Esperado (1/2/3)'], $filasCat);

        $c->warn('Advertencias (' . count($this->advertencias) . '):');
        foreach ($this->advertencias as $advertencia) {
            $c->line('  - ' . $advertencia);
        }

        $this->registrosPrevios($codigos);

        $c->newLine();
        $c->info('Verificación: los ' . count($filas) . ' equipos cargados');
        $c->table(
            ['codigo_patrimonial', 'nombre_equipo', 'seccion', 'aula', 'modelo', 'fecha_adquisicion', 'etapa_ciclo_vida', 'direccion_ip', 'version_firmware'],
            DB::table('activos_tecnologicos as a')
                ->join('ubicaciones as u', 'u.id_ubicacion', '=', 'a.fk_id_ubicacion')
                ->leftJoin('detalles_equipo_red as d', 'd.fk_id_activo', '=', 'a.id_activo')
                ->whereIn('a.codigo_patrimonial', $codigos)
                ->orderBy('a.fk_id_categoria')->orderBy('a.id_activo')
                ->get(['a.codigo_patrimonial', 'a.nombre_equipo', 'u.seccion', 'u.aula', 'a.modelo', 'a.fecha_adquisicion', 'a.etapa_ciclo_vida', 'd.direccion_ip', 'd.version_firmware'])
                ->map(fn ($f) => array_map(fn ($v) => $v ?? '—', (array) $f))
                ->all()
        );
    }

    /** Registros de las categorías 2, 3 y 4 que NO son de esta carga: solo se listan, no se tocan. */
    private function registrosPrevios(array $codigosCargados): void
    {
        $c = $this->command;

        $previos = DB::table('activos_tecnologicos as a')
            ->join('categorias as k', 'k.id_categoria', '=', 'a.fk_id_categoria')
            ->join('ubicaciones as u', 'u.id_ubicacion', '=', 'a.fk_id_ubicacion')
            ->leftJoin('detalles_equipo_red as d', 'd.fk_id_activo', '=', 'a.id_activo')
            ->whereIn('a.fk_id_categoria', array_keys(self::CATEGORIAS))
            ->whereNotIn('a.codigo_patrimonial', $codigosCargados)
            ->orderBy('a.fk_id_categoria')->orderBy('a.id_activo')
            ->get(['a.id_activo', 'k.nombre_categoria', 'a.codigo_patrimonial', 'a.nombre_equipo', 'a.marca', 'a.modelo', 'a.fecha_adquisicion', 'u.seccion', 'u.aula', 'd.direccion_ip']);

        $c->newLine();
        $c->warn('Registros previos a revisar (categorías 2, 3 y 4; no se modificaron): ' . $previos->count());

        if ($previos->isNotEmpty()) {
            $c->table(
                ['id', 'categoría', 'código', 'nombre', 'marca', 'modelo', 'fecha_adquisicion', 'sección', 'aula', 'IP'],
                $previos->map(fn ($f) => array_map(fn ($v) => $v ?? '—', array_values((array) $f)))->all()
            );
        }
    }
}
