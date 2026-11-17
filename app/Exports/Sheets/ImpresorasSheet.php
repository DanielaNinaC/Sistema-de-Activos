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

class ImpresorasSheet implements FromCollection, WithCustomStartCell, WithStyles, WithTitle
{
    private Collection $activos;

    public function __construct(Collection $activos)
    {
        $this->activos = $activos;
    }

    public function title(): string
    {
        return 'IMPRESORAS';
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
            'AULA / SECCIÓN',
            'CÓDIGO',
            'EQUIPO / MODELO',
            'MARCA',
            'DIRECCIÓN IP / RED',
            'FIRMWARE',
            'AÑO COMPRA',
            'ESTADO',
            'OBSERVACIONES',
        ];

        $i = 1;
        foreach ($this->activos as $activo) {
            $ubicacion = $activo->ubicacion ? ($activo->ubicacion->seccion . (!empty($activo->ubicacion->aula) ? ' - ' . $activo->ubicacion->aula : '')) : 'S/U';
            $red = $activo->detalleEquipoRed;
            $ip = $red?->direccion_ip ?? 'USB / Local';
            $firmware = $red?->version_firmware ?? '-';
            $anio = $activo->fecha_adquisicion ? date('Y', strtotime($activo->fecha_adquisicion)) : '-';
            $estadoNum = SheetHelper::mapearEstado($activo->estado_operativo);
            $obs = $activo->observaciones ?: 'Sin observaciones';

            $filas[] = [
                $i++,
                $ubicacion,
                $activo->codigo_patrimonial,
                $activo->nombre_equipo . ($activo->modelo ? ' (' . $activo->modelo . ')' : ''),
                $activo->marca ?? '-',
                $ip,
                $firmware,
                $anio,
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
        SheetHelper::agregarLogoYTitulo($sheet, 'C1:J1', 'IMPRESORAS Y MULTIFUNCIONALES', '2E4053');

        // 2. Encabezados Fila 3
        SheetHelper::aplicarEncabezadosTabla($sheet, 'A3:J3', '2E4053');

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
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(26);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(35);
        $sheet->getColumnDimension('K')->setWidth(4);
        $sheet->getColumnDimension('L')->setWidth(22);
        $sheet->getColumnDimension('M')->setWidth(8);
        $sheet->getColumnDimension('N')->setWidth(14);

        return [];
    }
}
