<?php

namespace App\Exports\Sheets;

use App\Models\ActivoTecnologico;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CarritosSheet implements FromCollection, WithCustomStartCell, WithStyles, WithTitle
{
    private Collection $activos;
    /** @var array<int, array{seccion: string, start: int, end: int}> */
    private array $bloquesSeccion = [];

    public function __construct(Collection $activos)
    {
        // Ordenamos por seccion para facilitar la fusión vertical de bloques
        $this->activos = $activos->sortBy(function ($a) {
            $sec = $a->ubicacion->seccion ?? 'GENERAL';
            return strtolower($sec);
        })->values();
    }

    public function title(): string
    {
        return 'CARRITOS';
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function collection(): Collection
    {
        $filas = [];
        // Fila 3: Encabezados
        $filas[] = [
            'SECCIÓN',
            'item',
            'RAM',
            'PROCESADOR',
            'DISCO SÓLIDO (GB)',
            'LECTOR',
            'TECLADO / MOUSE',
            'S.O.',
            'ANTIGÜEDAD',
            'OBSERVACIONES',
            'Estado',
        ];

        $filaActual = 4;
        $grupos = $this->activos->groupBy(function ($a) {
            $sec = $a->ubicacion->seccion ?? '';
            $cat = $a->categoria->nombre_categoria ?? 'MÓVILES';
            if ($sec) {
                return strtoupper($sec . ' (' . $cat . ')');
            }
            return 'EQUIPOS MÓVILES (' . strtoupper($cat) . ')';
        });

        $itemGlobal = 1;
        foreach ($grupos as $nombreSeccion => $activosGrupo) {
            $inicioBloque = $filaActual;

            foreach ($activosGrupo as $activo) {
                $det = $activo->detalleComputadora;
                $ram = $det?->ram_gb ? $det->ram_gb . ' GB' : '-';
                $proc = $det?->procesador ?? $activo->nombre_equipo ?? '-';
                $disco = $det?->disco_solido_gb ? $det->disco_solido_gb . ' GB' : ($det?->disco_rigido_gb ? $det->disco_rigido_gb . ' GB (HDD)' : '-');
                $lector = $det ? ($det->lector ? 'SÍ' : 'NO') : 'NO';
                $tecladoMouse = $det ? ($det->teclado_mouse ? 'SÍ' : 'NO') : 'INTEGRADO';
                $so = $det?->sistema_operativo ?? '-';
                $antiguedad = $activo->fecha_adquisicion ? date('Y', strtotime($activo->fecha_adquisicion)) : '-';
                $obs = $activo->observaciones ?: ($activo->codigo_patrimonial . ' - ' . ($activo->marca ?? '') . ' ' . ($activo->modelo ?? ''));
                $estadoNum = SheetHelper::mapearEstado($activo->estado_operativo);

                $filas[] = [
                    $nombreSeccion,
                    $itemGlobal++,
                    $ram,
                    $proc,
                    $disco,
                    $lector,
                    $tecladoMouse,
                    $so,
                    $antiguedad,
                    $obs,
                    $estadoNum,
                ];

                $filaActual++;
            }

            $finBloque = $filaActual - 1;
            $this->bloquesSeccion[] = [
                'seccion' => $nombreSeccion,
                'start' => $inicioBloque,
                'end' => $finBloque,
            ];
        }

        return collect($filas);
    }

    public function styles(Worksheet $sheet)
    {
        $anio = date('Y');

        // 1. Encabezado General con logo institucional y título
        SheetHelper::agregarLogoYTitulo($sheet, 'C1:K1', 'EQUIPOS MÓVILES (LAPTOPS / TABLETS)', '2C3E50');

        // 2. Encabezados de tabla (Fila 3)
        SheetHelper::aplicarEncabezadosTabla($sheet, 'A3:K3', '2C3E50');

        $filaInicio = 4;
        $totalActivos = $this->activos->count();
        $filaFin = max(3 + $totalActivos, 4);

        if ($totalActivos > 0) {
            // Bordes en toda la tabla
            SheetHelper::aplicarBordes($sheet, "A3:K{$filaFin}");

            // 3. Fusión vertical de secciones y orientación de texto vertical a 90°
            foreach ($this->bloquesSeccion as $bloque) {
                $start = $bloque['start'];
                $end = $bloque['end'];

                if ($start < $end) {
                    $sheet->mergeCells("A{$start}:A{$end}");
                }

                $sheet->getStyle("A{$start}:A{$end}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A235A']], // Morado oscuro ejecutivo
                    'alignment' => [
                        'textRotation' => 90,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
            }

            // Alineaciones
            // [B] item - centrado
            $sheet->getStyle("B{$filaInicio}:B{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [C] RAM - centrado
            $sheet->getStyle("C{$filaInicio}:C{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [D] PROCESADOR - centrado
            $sheet->getStyle("D{$filaInicio}:D{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [E] DISCO SÓLIDO - centrado
            $sheet->getStyle("E{$filaInicio}:E{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [F] LECTOR - centrado
            $sheet->getStyle("F{$filaInicio}:F{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [G] TECLADO/MOUSE - centrado
            $sheet->getStyle("G{$filaInicio}:G{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [H] S.O. - centrado
            $sheet->getStyle("H{$filaInicio}:H{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [I] ANTIGÜEDAD - centrado
            $sheet->getStyle("I{$filaInicio}:I{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [J] OBSERVACIONES - izquierda con wrapText
            $sheet->getStyle("J{$filaInicio}:J{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            // [K] Estado - centrado
            $sheet->getStyle("K{$filaInicio}:K{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Formato condicional y semáforo en columna K
            SheetHelper::aplicarFormatoCondicionalEstado($sheet, 'K', $filaInicio, $filaFin);
        } else {
            SheetHelper::aplicarBordes($sheet, 'A3:K3');
        }

        // 4. Tabla Resumen / Semáforo en columnas M, N, O
        SheetHelper::crearTablaSemaforo($sheet, 'M', 'N', 'O', 'K', $filaInicio, max($filaFin, 100));

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(8);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(35);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(4);
        $sheet->getColumnDimension('M')->setWidth(22);
        $sheet->getColumnDimension('N')->setWidth(8);
        $sheet->getColumnDimension('O')->setWidth(14);

        return [];
    }
}
