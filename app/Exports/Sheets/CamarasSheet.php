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

class CamarasSheet implements FromCollection, WithCustomStartCell, WithStyles, WithTitle
{
    private Collection $activos;

    public function __construct(Collection $activos)
    {
        $this->activos = $activos;
    }

    public function title(): string
    {
        return 'CÁMARAS';
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
            'SECTOR / UBICACIÓN',
            'CÓDIGO',
            'MARCA / MODELO',
            'DIRECCIÓN IP / TIPO',
            'FIRMWARE',
            'AÑO COMPRA',
            'ESTADO',
            'OBSERVACIONES',
        ];

        $i = 1;
        foreach ($this->activos as $activo) {
            $ubicacion = $activo->ubicacion ? ($activo->ubicacion->seccion . (!empty($activo->ubicacion->aula) ? ' - ' . $activo->ubicacion->aula : '')) : 'S/U';
            $red = $activo->detalleEquipoRed;
            $ip = $red?->direccion_ip ?? 'IP / Coaxial';
            $firmware = $red?->version_firmware ?? '-';
            $marcaModelo = trim(($activo->marca ?? '') . ' ' . ($activo->modelo ?? '')) ?: $activo->nombre_equipo;
            $anio = $activo->fecha_adquisicion ? date('Y', strtotime($activo->fecha_adquisicion)) : '-';
            $estadoNum = SheetHelper::mapearEstado($activo->estado_operativo);
            $obs = $activo->observaciones ?: 'Sin observaciones';

            $filas[] = [
                $i++,
                $ubicacion,
                $activo->codigo_patrimonial,
                $marcaModelo,
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
        SheetHelper::agregarLogoYTitulo($sheet, 'C1:I1', 'CÁMARAS CCTV Y SEGURIDAD', '212F3D');

        // 2. Encabezados Fila 3
        SheetHelper::aplicarEncabezadosTabla($sheet, 'A3:I3', '212F3D');

        $filaInicio = 4;
        $total = $this->activos->count();
        $filaFin = max(3 + $total, 4);

        if ($total > 0) {
            SheetHelper::aplicarBordes($sheet, "A3:I{$filaFin}");

            $sheet->getStyle("A{$filaInicio}:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$filaInicio}:B{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$filaInicio}:C{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D{$filaInicio}:D{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E{$filaInicio}:E{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("F{$filaInicio}:F{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("G{$filaInicio}:G{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("H{$filaInicio}:H{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("I{$filaInicio}:I{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            SheetHelper::aplicarFormatoCondicionalEstado($sheet, 'H', $filaInicio, $filaFin);
        } else {
            SheetHelper::aplicarBordes($sheet, 'A3:I3');
        }

        // 3. Tabla Resumen / Semáforo en K, L, M
        SheetHelper::crearTablaSemaforo($sheet, 'K', 'L', 'M', 'H', $filaInicio, max($filaFin, 100));

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(35);
        $sheet->getColumnDimension('J')->setWidth(4);
        $sheet->getColumnDimension('K')->setWidth(22);
        $sheet->getColumnDimension('L')->setWidth(8);
        $sheet->getColumnDimension('M')->setWidth(14);

        return [];
    }
}
