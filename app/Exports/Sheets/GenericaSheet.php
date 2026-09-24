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

class GenericaSheet implements FromCollection, WithCustomStartCell, WithStyles, WithTitle
{
    private string $nombreCategoria;
    private Collection $activos;

    public function __construct(string $nombreCategoria, Collection $activos)
    {
        $this->nombreCategoria = $nombreCategoria;
        $this->activos = $activos;
    }

    public function title(): string
    {
        // En Excel el nombre de pestaña tiene límite de 31 caracteres y no puede contener caracteres inválidos
        $limpio = preg_replace('/[\\\\\/*\?:\[\]]/', '', $this->nombreCategoria);
        return mb_substr(strtoupper($limpio), 0, 30);
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function collection(): Collection
    {
        $filas = [];
        $filas[] = [
            'Nº',
            'UBICACIÓN / AULA',
            'CÓDIGO',
            'NOMBRE DEL EQUIPO',
            'MARCA',
            'MODELO',
            'AÑO COMPRA',
            'ETAPA CICLO VIDA',
            'ESTADO',
            'OBSERVACIONES',
        ];

        $i = 1;
        foreach ($this->activos as $activo) {
            $ubicacion = $activo->ubicacion ? ($activo->ubicacion->seccion . (!empty($activo->ubicacion->aula) ? ' - ' . $activo->ubicacion->aula : '')) : 'S/U';
            $anio = $activo->fecha_adquisicion ? date('Y', strtotime($activo->fecha_adquisicion)) : '-';
            $estadoNum = SheetHelper::mapearEstado($activo->estado_operativo);
            $obs = $activo->observaciones ?: 'Sin observaciones';

            $filas[] = [
                $i++,
                $ubicacion,
                $activo->codigo_patrimonial,
                $activo->nombre_equipo,
                $activo->marca ?? '-',
                $activo->modelo ?? '-',
                $anio,
                $activo->etapa_ciclo_vida ?? '-',
                $estadoNum,
                $obs,
            ];
        }

        return collect($filas);
    }

    public function styles(Worksheet $sheet)
    {
        $anio = date('Y');
        // 1. Encabezado General con logo institucional y título
        SheetHelper::agregarLogoYTitulo($sheet, 'C1:J1', $this->nombreCategoria, '2C3E50');

        // 2. Encabezados Fila 3
        SheetHelper::aplicarEncabezadosTabla($sheet, 'A3:J3', '2C3E50');

        $filaInicio = 4;
        $total = $this->activos->count();
        $filaFin = max(3 + $total, 4);

        if ($total > 0) {
            SheetHelper::aplicarBordes($sheet, "A3:J{$filaFin}");

            $sheet->getStyle("A{$filaInicio}:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$filaInicio}:B{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$filaInicio}:C{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D{$filaInicio}:D{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E{$filaInicio}:E{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("F{$filaInicio}:F{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("G{$filaInicio}:G{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("H{$filaInicio}:H{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("I{$filaInicio}:I{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("J{$filaInicio}:J{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            SheetHelper::aplicarFormatoCondicionalEstado($sheet, 'I', $filaInicio, $filaFin);
        } else {
            SheetHelper::aplicarBordes($sheet, 'A3:J3');
        }

        // 3. Tabla Resumen / Semáforo en L, M, N
        SheetHelper::crearTablaSemaforo($sheet, 'L', 'M', 'N', 'I', $filaInicio, max($filaFin, 100));

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(35);
        $sheet->getColumnDimension('K')->setWidth(4);
        $sheet->getColumnDimension('L')->setWidth(22);
        $sheet->getColumnDimension('M')->setWidth(8);
        $sheet->getColumnDimension('N')->setWidth(14);

        return [];
    }
}
