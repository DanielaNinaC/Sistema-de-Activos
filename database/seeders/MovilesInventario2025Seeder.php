<?php

namespace Database\Seeders;

use App\Models\ActivoTecnologico;
use App\Models\DetalleComputadora;
use App\Models\Log;
use App\Models\Mantenimiento;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Support\Catalogo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Actualiza con el "Inventario técnico de equipos de computación móviles –
 * Gestión 2025" los laptops (cat. 6) y tablets (cat. 7) ya importados, y crea
 * la única tablet que falta (Maternal #32, ya dada de baja).
 *
 *   php artisan db:seed --class=MovilesInventario2025Seeder
 *
 * Atómico (todo o nada) e idempotente: los equipos se buscan por
 * origen_externo y nunca se modifican codigo_patrimonial, origen_externo,
 * nombre_equipo, fk_id_ubicacion ni nivel_riesgo. No ejecuta evaluaciones de riesgo.
 */
class MovilesInventario2025Seeder extends Seeder
{
    private const PREFIJO_TABLET_MATERNAL = 'TABLETS|SALA DE ARTES-MATERNAL (TABLETS)|PROFESORES';
    private const NUEVA_TABLET_ITEM = 32;
    private const NUEVA_TABLET_CODIGO = 'TAB-SC-32';

    private const CONTEO_ESTADOS = [1 => 140, 2 => 40, 3 => 10];

    /** El catálogo `motivos_baja` (con llave foránea) no admite texto libre: se usa el motivo equivalente. */
    private const MOTIVO_BAJA = 'Robo o extravío';

    /**
     * Segmentos en el orden del inventario (de él depende la secuencia de
     * fechas aleatorias). `desde`/`hasta` son ítems del inventario; el ítem en la
     * base es ítem - desplazamiento.
     */
    private const SEGMENTOS = [
        ['grupo' => 'maternal', 'categoria' => 7, 'prefijo' => self::PREFIJO_TABLET_MATERNAL, 'desde' => 1, 'hasta' => 32, 'desplazamiento' => 0,
            'anio' => 2021, 'ram' => 4, 'cpu' => null, 'ssd' => null, 'lector' => 0, 'so' => 'Android 11',
            'marca' => 'Samsung', 'modelo' => 'Galaxy Tab S6 Lite'],
        ['grupo' => 'prim_lap', 'categoria' => 6, 'prefijo' => 'LAPTOPS|BLOQUES DE PRIMARIA (LAPTOPS)|P1 INTERMEDIO', 'desde' => 1, 'hasta' => 7, 'desplazamiento' => 0,
            'anio' => 2018, 'ram' => 4, 'cpu' => 'Core i5', 'ssd' => 240, 'lector' => 1, 'so' => 'Windows 10'],
        ['grupo' => 'prim_and', 'categoria' => 7, 'prefijo' => 'TABLETS|BLOQUES DE PRIMARIA (LAPTOPS)|P1 INTERMEDIO', 'desde' => 8, 'hasta' => 13, 'desplazamiento' => 7,
            'anio' => 2017, 'ram' => 2, 'cpu' => 'Quad Core 1,2 GHz', 'ssd' => 8, 'lector' => 0, 'so' => 'Android 7.1.1', 'modelo' => null],
        ['grupo' => 'prim_and', 'categoria' => 7, 'prefijo' => 'TABLETS|BLOQUES DE PRIMARIA (LAPTOPS)|P1 INTERMEDIO', 'desde' => 14, 'hasta' => 20, 'desplazamiento' => 7,
            'anio' => 2017, 'ram' => 2, 'cpu' => 'Quad Core 1,2 GHz', 'ssd' => 8, 'lector' => 0, 'so' => 'Android 6', 'modelo' => null],
        ['grupo' => 'mixto', 'categoria' => 6, 'prefijo' => 'LAPTOPS|BLOQUES MIXTO (LAPTOPS)|P1 INTERMEDIO', 'desde' => 1, 'hasta' => 23, 'desplazamiento' => 0,
            'anio' => 2017, 'ram' => 4, 'cpu' => 'Core i5', 'ssd' => 240, 'lector' => 1, 'so' => 'Windows 10'],
        ['grupo' => 'mate', 'categoria' => 6, 'prefijo' => 'LAPTOPS|LAPTOS BLOQUE SECUNDARIA MATEMATICAS|LAB. CIENCIAS', 'desde' => 1, 'hasta' => 25, 'desplazamiento' => 0,
            'anio' => 2021, 'ram' => 8, 'cpu' => 'Core i7 2,70 GHz', 'ssd' => 240, 'lector' => 0, 'so' => 'Windows 10'],
        ['grupo' => 'pbaja', 'categoria' => 6, 'prefijo' => 'LAPTOPS|LAPTOS BLOQUE SECUNDARIA PLANTA BAJA|LAB. CIENCIAS', 'desde' => 1, 'hasta' => 14, 'desplazamiento' => 0,
            'anio' => 2021, 'ram' => 8, 'cpu' => 'Core i7 2,70 GHz', 'ssd' => 240, 'lector' => 0, 'so' => 'Windows 10'],
        ['grupo' => 'pbaja', 'categoria' => 6, 'prefijo' => 'LAPTOPS|LAPTOS BLOQUE SECUNDARIA PLANTA BAJA|LAB. CIENCIAS', 'desde' => 15, 'hasta' => 25, 'desplazamiento' => 0,
            'anio' => 2022, 'ram' => 8, 'cpu' => 'Core i7 2,70 GHz', 'ssd' => 240, 'lector' => 0, 'so' => 'Windows 10'],
        ['grupo' => 'palta', 'categoria' => 6, 'prefijo' => 'LAPTOPS|LAPTOS BLOQUE SECUNDARIA PLANTA ALTA|LAB. CIENCIAS', 'desde' => 1, 'hasta' => 25, 'desplazamiento' => 0,
            'anio' => 2022, 'ram' => 8, 'cpu' => 'Core i7 2,70 GHz', 'ssd' => 240, 'lector' => 0, 'so' => 'Windows 10'],
        ['grupo' => 'exao', 'categoria' => 6, 'prefijo' => 'LAPTOPS|LAPTOS BLOQUE SECUNDARIA EXAO|LAB. CIENCIAS', 'desde' => 1, 'hasta' => 20, 'desplazamiento' => 0,
            'anio' => 2021, 'ram' => 8, 'cpu' => 'Core i7 2,70 GHz', 'ssd' => 240, 'lector' => 1, 'so' => 'Windows 10'],
        ['grupo' => 'cdi', 'categoria' => 6, 'prefijo' => 'LAPTOPS|LAPTOS BLOQUE SECUNDARIA CDI|LAB. CIENCIAS', 'desde' => 1, 'hasta' => 20, 'desplazamiento' => 0,
            'anio' => 2021, 'ram' => 8, 'cpu' => 'Core i7 2,70 GHz', 'ssd' => 240, 'lector' => 1, 'so' => 'Windows 10'],
    ];

    private const GRUPOS = [
        'maternal' => 'Sala de Artes-Maternal (tablets)',
        'prim_lap' => 'Bloques de Primaria (laptops)',
        'prim_and' => 'Bloques de Primaria (Android)',
        'mixto' => 'Bloques Mixto (laptops)',
        'mate' => 'Secundaria Matemáticas',
        'pbaja' => 'Secundaria Planta Baja',
        'palta' => 'Secundaria Planta Alta',
        'exao' => 'Secundaria EXAO',
        'cdi' => 'Secundaria CDI',
    ];

    /** Estado 3 del inventario: grupo => ítems. */
    private const BAJAS = ['maternal' => [30, 31, 32], 'mate' => [24, 25], 'exao' => [19]];
    private const INOPERATIVOS = ['mixto' => [21, 22, 23], 'exao' => [20]];

    private const TXT_ANTIGUO = 'Equipo antiguo; se sugiere cambio de piezas para mejorar el rendimiento.';
    private const TXT_DISCO = 'Capacidad de disco normalizada a 240 GB (valor 241–245 en el inventario).';
    private const TXT_DANADO = 'Dañado según inventario técnico 2025.';
    private const TXT_BAJA = 'Dado de baja: equipo extraviado según inventario técnico 2025.';
    private const TXT_NUEVA = 'Código patrimonial provisional; equipo extraviado según inventario técnico 2025.';

    /** @var string[] */
    private array $advertencias = [];

    /** @var array<int, array{0: string, 1: string, 2: mixed, 3: mixed}> */
    private array $cambios = [];

    private array $contadores = [
        'actualizados' => 0, 'con_cambios' => 0, 'creados' => 0, 'bajas' => 0, 'ya_de_baja' => 0,
        'inoperativos' => 0, 'ya_inoperativos' => 0, 'fichas_nuevas' => 0, 'fichas_actualizadas' => 0,
    ];

    private ?int $idUsuarioLog = null;

    public function run(): void
    {
        $equipos = $this->construirEquipos();
        $this->validar($equipos);

        // Semilla fija: las fechas aleatorias son las mismas en cada ejecución.
        mt_srand(2026);

        DB::transaction(function () use ($equipos) {
            $existentes = $this->cargarExistentes($equipos);
            $this->idUsuarioLog = Usuario::where('fk_id_rol', config('roles.admin'))->orderBy('id_usuario')->value('id_usuario')
                ?? Usuario::orderBy('id_usuario')->value('id_usuario');

            foreach ($equipos as $equipo) {
                // La fecha se sortea en el orden del inventario, exista o no el equipo.
                $fecha = Carbon::create($equipo['anio'], mt_rand(1, 12), mt_rand(1, 28))->toDateString();

                $activo = $existentes[$equipo['origen']] ?? null;

                $activo
                    ? $this->actualizar($activo, $equipo, $fecha)
                    : $this->crearTabletNueva($equipo, $fecha);
            }
        });

        $this->resumen($equipos);
    }

    // ── Construcción y validación del inventario ────────────────────────

    /** @return array<int, array<string, mixed>> */
    private function construirEquipos(): array
    {
        $equipos = [];

        foreach (self::SEGMENTOS as $seg) {
            for ($item = $seg['desde']; $item <= $seg['hasta']; $item++) {
                $base = $item - $seg['desplazamiento'];

                $equipos[] = $seg + [
                    'item' => $item,
                    'origen' => $seg['prefijo'] . '|' . $base,
                    'estado' => $this->estadoInventario($seg['grupo'], $item),
                    'disco_normalizado' => in_array($seg['grupo'], ['mate', 'pbaja', 'palta'], true) && $item >= 20 && $item <= 24,
                    'etiqueta' => self::GRUPOS[$seg['grupo']] . ' #' . $item,
                ];
            }
        }

        return $equipos;
    }

    private function estadoInventario(string $grupo, int $item): int
    {
        if (in_array($item, self::BAJAS[$grupo] ?? [], true) || in_array($item, self::INOPERATIVOS[$grupo] ?? [], true)) {
            return 3;
        }

        $antiguo = in_array($grupo, ['prim_lap', 'prim_and'], true) || ($grupo === 'mixto' && $item <= 20);

        return $antiguo ? 2 : 1;
    }

    private function validar(array $equipos): void
    {
        $conteo = array_count_values(array_column($equipos, 'estado'));
        ksort($conteo);

        if (count($equipos) !== 190 || $conteo !== self::CONTEO_ESTADOS) {
            throw new RuntimeException(
                'Inventario inconsistente: se esperaban 190 equipos con estados ' . json_encode(self::CONTEO_ESTADOS)
                . ' y se armaron ' . count($equipos) . ' con ' . json_encode($conteo) . '; no se cambió nada.'
            );
        }

        if (count(array_unique(array_column($equipos, 'origen'))) !== 190) {
            throw new RuntimeException('Hay equipos repetidos en el inventario; no se cambió nada.');
        }
    }

    /**
     * Busca todos los equipos por origen_externo. Si falta alguno (salvo la
     * Tablet Maternal #32) se detiene informando cuáles.
     *
     * @return array<string, ActivoTecnologico>
     */
    private function cargarExistentes(array $equipos): array
    {
        $encontrados = ActivoTecnologico::whereIn('origen_externo', array_column($equipos, 'origen'))->get()->keyBy('origen_externo');

        $faltantes = [];
        foreach ($equipos as $e) {
            if ($encontrados->has($e['origen'])) {
                if ((int) $encontrados[$e['origen']]->fk_id_categoria !== $e['categoria']) {
                    $faltantes[] = "{$e['etiqueta']} ({$e['origen']}): está en otra categoría distinta de {$e['categoria']}";
                }
                continue;
            }

            if (!$this->esTabletNueva($e)) {
                $faltantes[] = "{$e['etiqueta']} ({$e['origen']})";
            }
        }

        if ($faltantes) {
            throw new RuntimeException("No se encontraron equipos esperados; no se cambió nada:\n  - " . implode("\n  - ", $faltantes));
        }

        return $encontrados->all();
    }

    private function esTabletNueva(array $e): bool
    {
        return $e['grupo'] === 'maternal' && $e['item'] === self::NUEVA_TABLET_ITEM;
    }

    // ── Actualización de un equipo existente ────────────────────────────

    private function actualizar(ActivoTecnologico $a, array $e, string $fecha): void
    {
        $this->contadores['actualizados']++;
        $a->fecha_adquisicion = $fecha;

        if (array_key_exists('marca', $e)) {
            $a->marca = $e['marca'];
        }
        if (array_key_exists('modelo', $e)) {
            $a->modelo = $e['modelo'];
        }

        $actual = $a->getOriginal('estado_operativo');
        $textos = [];

        // Excepción: con mantenimiento abierto, un equipo 'En Mantenimiento' conserva su estado operativo.
        $conservaEstado = $actual === 'En Mantenimiento'
            && Mantenimiento::where('fk_id_activo', $a->id_activo)->whereIn('estado_mantenimiento', Catalogo::estadosMantenimientoAbiertos())->exists();

        $esBaja = in_array($e['item'], self::BAJAS[$e['grupo']] ?? [], true);
        $esInoperativo = in_array($e['item'], self::INOPERATIVOS[$e['grupo']] ?? [], true);
        $bajaAhora = false;

        if ($conservaEstado) {
            $this->advertencias[] = "{$e['etiqueta']}: tiene un mantenimiento abierto y está 'En Mantenimiento'; se conservó su estado operativo.";
            if (!$esBaja && !$esInoperativo) {
                $a->etapa_ciclo_vida = $e['estado'] === 2 ? 'Mantenimiento' : 'Operación';
            }
        } elseif ($esBaja) {
            if ($actual === 'De Baja') {
                $this->contadores['ya_de_baja']++;
            } else {
                $this->aplicarBaja($a, $actual);
                $textos[] = self::TXT_BAJA;
                $bajaAhora = true;
                $this->contadores['bajas']++;
            }
        } elseif ($esInoperativo) {
            $actual === 'Inoperativo' ? $this->contadores['ya_inoperativos']++ : $this->contadores['inoperativos']++;
            $a->estado_operativo = 'Inoperativo';
            $a->etapa_ciclo_vida = 'Renovación / Retiro';
            $textos[] = self::TXT_DANADO;
        } elseif ($actual === 'De Baja') {
            // Nunca se reactiva un equipo dado de baja por una actualización masiva.
            $this->advertencias[] = "{$e['etiqueta']}: está 'De Baja' y el inventario lo da por operativo; se dejó 'De Baja'.";
        } else {
            if ($actual === 'Inoperativo') {
                $this->advertencias[] = "{$e['etiqueta']}: estaba 'Inoperativo' y el inventario lo da por operativo (estado {$e['estado']}); pasó a 'Operativo'. Revisar.";
            }
            $a->estado_operativo = 'Operativo';
            $a->etapa_ciclo_vida = $e['estado'] === 2 ? 'Mantenimiento' : 'Operación';
        }

        if ($e['estado'] === 2) {
            $textos[] = self::TXT_ANTIGUO;
        }
        if ($e['disco_normalizado']) {
            $textos[] = self::TXT_DISCO;
        }

        foreach ($textos as $texto) {
            $a->observaciones = $this->agregarTexto($a->observaciones, $texto);
        }

        $this->registrarCambios($e['etiqueta'], $a->getDirty(), $a);

        if ($a->isDirty()) {
            $this->contadores['con_cambios']++;
        }
        $a->save();

        if ($bajaAhora) {
            $this->registrarBaja($a, $actual);
        }

        $this->guardarFicha($a, $e);
    }

    /** Aplica los campos de baja (el proyecto no tiene un servicio reutilizable: la lógica está en el controlador). */
    private function aplicarBaja(ActivoTecnologico $a, ?string $estadoPrevio): void
    {
        $a->estado_previo_baja = $estadoPrevio;
        $a->estado_operativo = 'De Baja';
        $a->etapa_ciclo_vida = 'Renovación / Retiro';
        $a->fecha_baja = now()->toDateString();
        $a->motivo_baja = self::MOTIVO_BAJA;
        $a->fk_id_usuario_baja = null;
    }

    private function registrarBaja(ActivoTecnologico $a, ?string $estadoPrevio): void
    {
        Log::create([
            'id_usuario' => $this->idUsuarioLog,
            'accion_realizada' => 'Baja de Activo Tecnológico',
            'tabla_afectada' => 'activos_tecnologicos',
            'registro_id' => $a->id_activo,
            'fecha_hora' => now(),
            'valor_anterior' => 'Estado operativo: ' . ($estadoPrevio ?? '—'),
            'valor_nuevo' => 'De Baja — ' . self::MOTIVO_BAJA . ' (inventario técnico 2025)',
        ]);
    }

    // ── Tablet Maternal #32 (única que se crea) ─────────────────────────

    private function crearTabletNueva(array $e, string $fecha): void
    {
        $ubicacion = Ubicacion::where('seccion', 'Maternal')->where('aula', 'Sala de Artes - Maternal')->first();
        if (!$ubicacion) {
            throw new RuntimeException("No existe la ubicación Maternal / Sala de Artes - Maternal para crear la Tablet Maternal #32; no se cambió nada.");
        }

        $a = ActivoTecnologico::unguarded(function () use ($e, $fecha, $ubicacion) {
            $a = new ActivoTecnologico([
                'fk_id_categoria' => 7,
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'codigo_patrimonial' => self::NUEVA_TABLET_CODIGO,
                'origen_externo' => $e['origen'],
                'nombre_equipo' => 'Tablet Maternal #32',
                'marca' => $e['marca'],
                'modelo' => $e['modelo'],
                'fecha_adquisicion' => $fecha,
                'observaciones' => self::TXT_NUEVA,
                'nivel_riesgo' => null,
            ]);
            $this->aplicarBaja($a, 'Operativo');

            return $a;
        });

        $a->save();
        $this->registrarBaja($a, 'Operativo');

        $this->contadores['creados']++;
        $this->contadores['bajas']++;
        $this->cambios[] = [$e['etiqueta'], '(equipo nuevo)', '—', self::NUEVA_TABLET_CODIGO . ' · De Baja · ' . $fecha];

        $this->guardarFicha($a, $e);
        $this->advertencias[] = "{$e['etiqueta']}: equipo creado con código provisional " . self::NUEVA_TABLET_CODIGO . ' y ya dado de baja.';
    }

    // ── Ficha técnica (detalles_computadora) ────────────────────────────

    private function guardarFicha(ActivoTecnologico $a, array $e): void
    {
        $ficha = DetalleComputadora::firstOrNew(['fk_id_activo' => $a->id_activo]);
        $esNueva = !$ficha->exists;

        // El inventario no trae disco rígido, parlantes, teclado/mouse, monitor ni cámara: no se tocan.
        $ficha->fill([
            'ram_gb' => $e['ram'],
            'procesador' => $e['cpu'],
            'disco_solido_gb' => $e['ssd'],
            'lector' => (bool) $e['lector'],
            'sistema_operativo' => $e['so'],
        ]);

        if ($esNueva) {
            $this->contadores['fichas_nuevas']++;
            foreach (['ram_gb', 'procesador', 'disco_solido_gb', 'lector', 'sistema_operativo'] as $campo) {
                $valor = $ficha->{$campo};
                $this->cambios[] = [$e['etiqueta'], "ficha.$campo", '(ficha nueva)', is_bool($valor) ? (int) $valor : ($valor ?? 'NULL')];
            }
        } elseif ($ficha->isDirty()) {
            $this->contadores['fichas_actualizadas']++;
            $this->registrarCambios($e['etiqueta'], $ficha->getDirty(), $ficha, 'ficha.');
        }

        $ficha->save();
    }

    // ── Utilidades ──────────────────────────────────────────────────────

    private function agregarTexto(?string $actual, string $texto): string
    {
        $actual = trim((string) $actual);

        return ($actual !== '' && str_contains($actual, $texto)) ? $actual : trim($actual . ' ' . $texto);
    }

    private function registrarCambios(string $etiqueta, array $sucios, $modelo, string $prefijo = ''): void
    {
        foreach ($sucios as $campo => $nuevo) {
            $antes = $modelo->getOriginal($campo);

            if (is_bool($nuevo) || in_array($campo, ['lector'], true)) {
                $antes = $antes === null ? null : (int) $antes;
                $nuevo = (int) $nuevo;
            }

            $this->cambios[] = [$etiqueta, $prefijo . $campo, $antes ?? 'NULL', $nuevo ?? 'NULL'];
        }
    }

    // ── Resumen ─────────────────────────────────────────────────────────

    private function resumen(array $equipos): void
    {
        $c = $this->command;
        $k = $this->contadores;

        $c->newLine();
        $c->info('Actualización del inventario técnico 2025 (laptops y tablets)');
        $c->table(['Concepto', 'Cantidad'], [
            ['Equipos del inventario', count($equipos)],
            ['Actualizados (equipos existentes procesados)', $k['actualizados']],
            ['  de ellos, con algún cambio en esta ejecución', $k['con_cambios']],
            ['Creados', $k['creados']],
            ['Dados de baja en esta ejecución (incluye el nuevo)', $k['bajas']],
            ['Ya estaban de baja (no se tocaron)', $k['ya_de_baja']],
            ['Marcados Inoperativo en esta ejecución', $k['inoperativos']],
            ['Ya estaban Inoperativo', $k['ya_inoperativos']],
            ['Fichas técnicas nuevas', $k['fichas_nuevas']],
            ['Fichas técnicas actualizadas', $k['fichas_actualizadas']],
        ]);

        $c->warn('Advertencias (' . count($this->advertencias) . '):');
        foreach ($this->advertencias as $advertencia) {
            $c->line('  - ' . $advertencia);
        }

        $porCampo = [];
        foreach ($this->cambios as [, $campo]) {
            $porCampo[$campo] = ($porCampo[$campo] ?? 0) + 1;
        }
        arsort($porCampo);
        $c->newLine();
        $c->info('Cambios por campo');
        $c->table(['Campo', 'Cambios'], array_map(fn ($campo, $n) => [$campo, $n], array_keys($porCampo), $porCampo));

        $c->newLine();
        $c->info('Tabla antes → después (' . count($this->cambios) . ' cambios)');
        $c->table(
            ['Equipo', 'Campo', 'Antes', 'Después'],
            array_map(fn ($f) => array_map(fn ($v) => is_bool($v) ? (string) (int) $v : (string) ($v ?? 'NULL'), $f), $this->cambios)
        );

        $this->verificacion($equipos);
    }

    private function verificacion(array $equipos): void
    {
        $c = $this->command;

        $filas = ActivoTecnologico::whereIn('origen_externo', array_column($equipos, 'origen'))
            ->get(['origen_externo', 'estado_operativo', 'etapa_ciclo_vida'])
            ->keyBy('origen_externo');

        $estados = ['Operativo', 'Inoperativo', 'De Baja', 'En Mantenimiento'];
        $etapas = ['Operación', 'Mantenimiento', 'Renovación / Retiro'];

        $porGrupo = [];
        foreach ($equipos as $e) {
            $f = $filas[$e['origen']] ?? null;
            $g = &$porGrupo[self::GRUPOS[$e['grupo']]];
            $g['equipos'] = ($g['equipos'] ?? 0) + 1;
            if ($f) {
                $g['e:' . $f->estado_operativo] = ($g['e:' . $f->estado_operativo] ?? 0) + 1;
                $g['t:' . $f->etapa_ciclo_vida] = ($g['t:' . $f->etapa_ciclo_vida] ?? 0) + 1;
            }
            unset($g);
        }

        $tabla = [];
        $totales = ['equipos' => 0];
        foreach ($porGrupo as $nombre => $g) {
            $fila = [$nombre, $g['equipos']];
            $totales['equipos'] += $g['equipos'];
            foreach ($estados as $s) {
                $fila[] = $g['e:' . $s] ?? 0;
                $totales['e:' . $s] = ($totales['e:' . $s] ?? 0) + ($g['e:' . $s] ?? 0);
            }
            foreach ($etapas as $t) {
                $fila[] = $g['t:' . $t] ?? 0;
                $totales['t:' . $t] = ($totales['t:' . $t] ?? 0) + ($g['t:' . $t] ?? 0);
            }
            $tabla[] = $fila;
        }

        $filaTotal = ['TOTAL', $totales['equipos']];
        foreach ($estados as $s) {
            $filaTotal[] = $totales['e:' . $s] ?? 0;
        }
        foreach ($etapas as $t) {
            $filaTotal[] = $totales['t:' . $t] ?? 0;
        }
        $tabla[] = $filaTotal;

        $c->newLine();
        $c->info('Verificación por grupo (estado operativo y etapa del ciclo de vida)');
        $c->table(array_merge(['Grupo', 'Equipos'], $estados, array_map(fn ($t) => "Etapa: $t", $etapas)), $tabla);

        $ok = ($totales['t:Operación'] ?? 0) === 140 && ($totales['t:Mantenimiento'] ?? 0) === 40 && ($totales['t:Renovación / Retiro'] ?? 0) === 10;
        $ok
            ? $c->info('Comprobación 140 / 40 / 10 (estado 1 / 2 / 3 del inventario ↔ Operación / Mantenimiento / Renovación-Retiro): CORRECTA.')
            : $c->error('Comprobación 140 / 40 / 10: NO coincide; revisar la tabla anterior.');
    }
}
