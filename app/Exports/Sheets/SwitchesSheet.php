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

class SwitchesSheet implements FromCollection, WithCustomStartCell, WithStyles, WithTitle
{
    private Collection $activos;

    public function __construct(Collection $activos)
    {
        $this->activos = $activos;
    }

    public function title(): string
    {
        return 'SWITCHES';
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
            'UBICACIÓN / RACK',
            'CÓDIGO',
            'TIPO DE EQUIPO',
            'NOMBRE / MODELO',
            'MARCA',
            'DIRECCIÓN IP',
            'FIRMWARE',
            'AÑO COMPRA',
            'ESTADO',
            'OBSERVACIONES',
        ];

        $i = 1;
        foreach ($this->activos as $activo) {
            $ubicacion = $activo->ubicacion ? ($activo->ubicacion->seccion . (!empty($activo->ubicacion->aula) ? ' - ' . $activo->ubicacion->aula : '')) : 'S/U';
            $red = $activo->detalleEquipoRed;
            $ip = $red?->direccion_ip ?? '-';
            $firmware = $red?->version_firmware ?? '-';
            $tipo = $activo->categoria->nombre_categoria ?? 'RED';
            $anio = $activo->fecha_adquisicion ? date('Y', strtotime($activo->fecha_adquisicion)) : '-';
            $estadoNum = SheetHelper::mapearEstado($activo->estado_operativo);
            $obs = $activo->observaciones ?: 'Sin observaciones';

            $filas[] = [
                $i++,
                $ubicacion,
                $activo->codigo_patrimonial,
                $tipo,
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
        SheetHelper::agregarLogoYTitulo($sheet, 'C1:K1', 'EQUIPOS DE RED Y COMUNICACIONES', '1A5276');

        // 2. Encabezados Fila 3
        SheetHelper::aplicarEncabezadosTabla($sheet, 'A3:K3', '1A5276');

        $filaInicio = 4;
        $total = $this->activos->count();
        $filaFin = max(3 + $total, 4);

        if ($total > 0) {
            SheetHelper::aplicarBordes($sheet, "A3:K{$filaFin}");

            $sheet->getStyle("A{$filaInicio}:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$filaInicio}:B{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$filaInicio}:C{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D{$filaInicio}:D{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E{$filaInicio}:E{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("F{$filaInicio}:F{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("G{$filaInicio}:G{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("H{$filaInicio}:H{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("I{$filaInicio}:I{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("J{$filaInicio}:J{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("K{$filaInicio}:K{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            SheetHelper::aplicarFormatoCondicionalEstado($sheet, 'J', $filaInicio, $filaFin);
        } else {
            SheetHelper::aplicarBordes($sheet, 'A3:K3');
        }

        // 3. Tabla Resumen / Semáforo en M, N, O
        SheetHelper::crearTablaSemaforo($sheet, 'M', 'N', 'O', 'J', $filaInicio, max($filaFin, 100));

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(26);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(35);
        $sheet->getColumnDimension('L')->setWidth(4);
        $sheet->getColumnDimension('M')->setWidth(22);
        $sheet->getColumnDimension('N')->setWidth(8);
        $sheet->getColumnDimension('O')->setWidth(14);

        return [];
    }
}
