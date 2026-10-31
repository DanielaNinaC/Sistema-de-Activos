<?php

namespace App\Services;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\DetalleComputadora;
use App\Models\DetalleEquipoRed;
use App\Models\DetalleProyector;
use App\Models\Ubicacion;
use Database\Support\NormalizacionMigracion as N;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * Importa el inventario de equipos desde un libro Excel guiándose por los
 * ENCABEZADOS de cada hoja (no por posición de columna) y por catálogos de la
 * base de datos: la ficha técnica de cada hoja sale de
 * categoría → tipo_activo → ficha_tecnica, no de nombres escritos en el código.
 *
 *  - Ignora las filas de título anteriores al encabezado.
 *  - SECCIÓN se arrastra hacia abajo cuando la celda está combinada o vacía.
 *  - "8GB" → 8; "Sí/No/X" → booleano; la IP debe ser válida.
 *  - Toda fila rechazada queda registrada con hoja, fila y motivo.
 *  - Es idempotente: la clave es codigo_patrimonial (existente → actualiza).
 *
 * Equivalencia de unidades: 1 TB = 1000 GB (convención de capacidad comercial).
 */
class ImportadorInventarioService
{
    /** Hoja (clave normalizada) → fragmento del nombre de la categoría en la base. */
    private const HOJAS = [
        'computadoras' => 'computadora',
        'carritos' => 'carrito',
        'impresoras' => 'impresora',
        'camaras' => 'camara',
        'proyectores' => 'proyector',
    ];

    /** Campo lógico → encabezados aceptados (ya normalizados con N::clave). */
    private const ALIAS = [
        'seccion' => ['seccion'],
        'aula' => ['aula', 'ubicacion', 'sala', 'ambiente'],
        'codigo' => ['codigo', 'codigopatrimonial', 'codpatrimonial', 'nroactivo', 'numeroactivo'],
        'nombre' => ['nombre', 'nombreequipo', 'equipo', 'hostname'],
        'marca' => ['marca'],
        'modelo' => ['modelo'],
        'procesador' => ['procesador', 'cpu'],
        'ram_gb' => ['ram', 'memoriaram', 'ramgb'],
        'sistema_operativo' => ['sistemaoperativo', 'so'],
        'disco_solido_gb' => ['ssd', 'discosolido', 'discossd'],
        'disco_rigido_gb' => ['hdd', 'discorigido', 'discoduro'],
        'direccion_ip' => ['ip', 'direccionip'],
        'tipo_cable' => ['cable', 'tipocable'],
        'calidad_imagen' => ['calidad', 'calidadimagen'],
        'estado' => ['estado', 'estadooperativo'],
        'anio' => ['ano', 'anio', 'fechaadquisicion', 'anioadquisicion'],
        'observaciones' => ['observaciones', 'nota', 'notas', 'obs'],
        'lector' => ['lector'],
        'parlantes' => ['parlantes'],
        'teclado_mouse' => ['tecladomouse', 'tecladoymouse'],
        'monitor' => ['monitor'],
        'camara_web' => ['camaraweb', 'webcam'],
    ];

    private const BOOLEANOS = ['lector', 'parlantes', 'teclado_mouse', 'monitor', 'camara_web'];

    /** Columnas de encabezado necesarias para reconocer la fila de títulos. */
    private const MINIMO_ENCABEZADOS = 3;

    /**
     * @param  array<string,string>  $mapaCategorias  clave de hoja → nombre exacto de categoría (para ambigüedades)
     * @return array{hojas:array<string,array<string,int>>,rechazadas:array<int,array{hoja:string,fila:int,motivo:string}>,advertencias:array<int,string>}
     */
    public function importar(string $ruta, bool $simular = false, array $mapaCategorias = []): array
    {
        $libro = IOFactory::load($ruta);
        $informe = ['hojas' => [], 'rechazadas' => [], 'advertencias' => []];

        $ejecutar = function () use ($libro, $simular, $mapaCategorias, &$informe) {
            foreach ($libro->getWorksheetIterator() as $hoja) {
                $clave = N::clave($hoja->getTitle());

                if (!isset(self::HOJAS[$clave])) {
                    $informe['advertencias'][] = "Hoja «{$hoja->getTitle()}» ignorada: no es una hoja de inventario conocida.";
                    continue;
                }

                $this->procesarHoja($hoja->getTitle(), $clave, $hoja->toArray(null, true, true, false), $simular, $mapaCategorias, $informe);
            }
        };

        $simular ? $ejecutar() : DB::transaction($ejecutar);

        return $informe;
    }

    private function procesarHoja(string $titulo, string $clave, array $filas, bool $simular, array $mapaCategorias, array &$informe): void
    {
        $informe['hojas'][$titulo] = ['leidas' => 0, 'creadas' => 0, 'actualizadas' => 0, 'rechazadas' => 0];
        $contador = &$informe['hojas'][$titulo];

        $categoria = $this->categoriaDeHoja($clave, $mapaCategorias);
        if (is_string($categoria)) {
            $informe['advertencias'][] = "Hoja «{$titulo}» omitida: {$categoria}";

            return;
        }

        $tipo = DB::table('tipos_activo as t')
            ->join('fichas_tecnicas as f', 'f.id_ficha_tecnica', '=', 't.fk_id_ficha_tecnica')
            ->where('t.fk_id_categoria', $categoria->id_categoria)
            ->first(['t.id_tipo_activo', 'f.codigo as ficha']);
        if (!$tipo) {
            $informe['advertencias'][] = "Hoja «{$titulo}» omitida: la categoría «{$categoria->nombre_categoria}» no tiene tipo de activo (ejecute normalizacion:sincronizar).";

            return;
        }

        [$filaEncabezado, $columnas] = $this->localizarEncabezado($filas);
        if ($filaEncabezado === null) {
            $informe['advertencias'][] = "Hoja «{$titulo}» omitida: no se encontró la fila de encabezados.";

            return;
        }

        $estados = DB::table('estados_operativos')->pluck('nombre')->mapWithKeys(fn ($n) => [N::clave($n) => $n])->all();
        $vistos = [];
        $seccionActual = null;

        foreach (array_slice($filas, $filaEncabezado + 1, null, true) as $indice => $fila) {
            $numeroFila = $indice + 1; // 1-based, como en Excel
            $dato = fn (string $campo) => isset($columnas[$campo]) ? $this->texto($fila[$columnas[$campo]] ?? null) : '';

            if (collect($fila)->every(fn ($c) => $this->texto($c) === '')) {
                continue; // fila vacía
            }

            if ($dato('seccion') !== '') {
                $seccionActual = $dato('seccion');
            }

            $contador['leidas']++;
            // Closure (no arrow fn): las arrow fn capturan por valor y perderían el conteo.
            $rechazo = function (string $motivo) use (&$informe, &$contador, $titulo, $numeroFila) {
                $this->rechazar($informe, $contador, $titulo, $numeroFila, $motivo);
            };

            $codigo = $dato('codigo');
            if ($codigo === '') {
                $rechazo('sin código patrimonial');
                continue;
            }
            if (isset($vistos[$codigo])) {
                $rechazo("código «{$codigo}» repetido en la hoja (primera aparición en la fila {$vistos[$codigo]})");
                continue;
            }
            $vistos[$codigo] = $numeroFila;

            if ($seccionActual === null || $dato('aula') === '') {
                $rechazo('sin sección o aula');
                continue;
            }

            $anio = $this->anio($dato('anio'));
            if ($anio === null) {
                $rechazo('sin año/fecha de adquisición válido');
                continue;
            }

            $estado = null;
            if ($dato('estado') !== '') {
                $estado = $estados[N::clave($dato('estado'))] ?? null;
                if ($estado === null) {
                    $rechazo("estado «{$dato('estado')}» fuera del catálogo");
                    continue;
                }
            }

            $detalle = [];
            $error = null;

            foreach (['ram_gb', 'disco_solido_gb', 'disco_rigido_gb'] as $campo) {
                if ($dato($campo) !== '') {
                    $valor = $this->gigas($dato($campo));
                    if ($valor === null) {
                        $error = "{$campo} «{$dato($campo)}» no es una capacidad válida";
                        break;
                    }
                    $detalle[$campo] = $valor;
                }
            }
            foreach (self::BOOLEANOS as $campo) {
                if ($error === null && $dato($campo) !== '') {
                    $valor = $this->booleano($dato($campo));
                    if ($valor === null) {
                        $error = "{$campo} «{$dato($campo)}» no es Sí/No";
                        break;
                    }
                    $detalle[$campo] = $valor;
                }
            }
            if ($error === null && $dato('direccion_ip') !== '' && !filter_var($dato('direccion_ip'), FILTER_VALIDATE_IP)) {
                $error = "IP «{$dato('direccion_ip')}» inválida";
            }
            if ($error !== null) {
                $rechazo($error);
                continue;
            }

            if ($simular) {
                $contador[ActivoTecnologico::where('codigo_patrimonial', $codigo)->exists() ? 'actualizadas' : 'creadas']++;
                continue;
            }

            $ubicacion = Ubicacion::firstOrCreate(['seccion' => $seccionActual, 'aula' => $dato('aula')]);

            $atributos = [
                'fk_id_categoria' => $categoria->id_categoria,
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'nombre_equipo' => $dato('nombre') !== '' ? $dato('nombre') : $codigo,
                'marca' => $dato('marca') ?: null,
                'modelo' => $dato('modelo') ?: null,
                'fecha_adquisicion' => $anio,
                'observaciones' => $dato('observaciones') ?: null,
            ];
            if ($estado !== null) {
                $atributos['estado_operativo'] = $estado;
            }

            $activo = ActivoTecnologico::where('codigo_patrimonial', $codigo)->first();
            if ($activo) {
                $activo->update($atributos);
                $contador['actualizadas']++;
            } else {
                $activo = ActivoTecnologico::create($atributos + ['codigo_patrimonial' => $codigo]);
                $contador['creadas']++;
            }

            $this->guardarFicha($tipo->ficha, $activo, $dato, $detalle);
        }
    }

    private function guardarFicha(string $ficha, ActivoTecnologico $activo, callable $dato, array $detalle): void
    {
        $clave = ['fk_id_activo' => $activo->id_activo];

        match ($ficha) {
            'COMPUTADORA' => DetalleComputadora::updateOrCreate($clave, $detalle + array_filter([
                'procesador' => $dato('procesador'),
                'sistema_operativo' => $dato('sistema_operativo'),
            ], fn ($v) => $v !== '')),
            'EQUIPO_DE_RED' => DetalleEquipoRed::updateOrCreate($clave, array_filter([
                'direccion_ip' => $dato('direccion_ip'),
            ], fn ($v) => $v !== '')),
            'PROYECTOR' => DetalleProyector::updateOrCreate($clave, array_filter([
                'tipo_cable' => $dato('tipo_cable'),
                'calidad_imagen' => $this->entero($dato('calidad_imagen')),
            ], fn ($v) => $v !== '' && $v !== null)),
            default => null, // SIN_FICHA_ESPECIFICA: no hay ficha que guardar
        };
    }

    /** @return Categoria|string  Categoría, o el motivo por el que no se pudo determinar */
    private function categoriaDeHoja(string $clave, array $mapaCategorias): Categoria|string
    {
        $todas = Categoria::all();

        if (isset($mapaCategorias[$clave])) {
            $elegida = $todas->first(fn ($c) => N::clave($c->nombre_categoria) === N::clave($mapaCategorias[$clave]));

            return $elegida ?? "la categoría «{$mapaCategorias[$clave]}» indicada no existe.";
        }

        $fragmento = self::HOJAS[$clave];
        $candidatas = $todas->filter(fn ($c) => str_contains(N::clave($c->nombre_categoria), $fragmento))->values();

        return match ($candidatas->count()) {
            1 => $candidatas->first(),
            0 => "no hay categoría que contenga «{$fragmento}»; indíquela con --categoria={$clave}=Nombre.",
            default => 'varias categorías coinciden (' . $candidatas->pluck('nombre_categoria')->implode(', ') . "); indíquela con --categoria={$clave}=Nombre.",
        };
    }

    /** @return array{0:?int,1:array<string,int>} índice de la fila de encabezados y campo → columna */
    private function localizarEncabezado(array $filas): array
    {
        foreach (array_slice($filas, 0, 15, true) as $indice => $fila) {
            $columnas = [];
            foreach ($fila as $posicion => $celda) {
                $clave = N::clave($this->texto($celda));
                foreach (self::ALIAS as $campo => $alias) {
                    if ($clave !== '' && in_array($clave, $alias, true) && !isset($columnas[$campo])) {
                        $columnas[$campo] = $posicion;
                    }
                }
            }

            if (count($columnas) >= self::MINIMO_ENCABEZADOS) {
                return [$indice, $columnas];
            }
        }

        return [null, []];
    }

    private function rechazar(array &$informe, array &$contador, string $hoja, int $fila, string $motivo): void
    {
        $contador['rechazadas']++;
        $informe['rechazadas'][] = ['hoja' => $hoja, 'fila' => $fila, 'motivo' => $motivo];
    }

    private function texto(mixed $valor): string
    {
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_float($valor) && floor($valor) === $valor) {
            $valor = (int) $valor;
        }

        return N::limpiar((string) $valor);
    }

    private function gigas(string $texto): ?int
    {
        if (!preg_match('/^(\d+(?:[.,]\d+)?)\s*(gb|g|tb|t)?$/i', trim($texto), $m)) {
            return null;
        }

        $valor = (float) str_replace(',', '.', $m[1]);
        if (isset($m[2]) && str_starts_with(strtolower($m[2]), 't')) {
            $valor *= 1000;
        }

        return $valor > 0 ? (int) round($valor) : null;
    }

    private function booleano(string $texto): ?bool
    {
        return match (N::clave($texto)) {
            'si', 'x', '1', 'true', 'yes', 'y', 's' => true,
            'no', '0', 'false', 'n' => false,
            default => null,
        };
    }

    private function entero(string $texto): ?int
    {
        return preg_match('/^\d+$/', $texto) ? (int) $texto : null;
    }

    /** Devuelve 'AAAA-01-01' para un año, o la fecha ISO si la celda trae fecha completa. */
    private function anio(string $texto): ?string
    {
        if (preg_match('/^(19|20)\d{2}$/', $texto)) {
            return $texto . '-01-01';
        }

        if (preg_match('/^(19|20)\d{2}-\d{2}-\d{2}$/', $texto) && strtotime($texto) !== false) {
            return $texto;
        }

        return null;
    }
}
