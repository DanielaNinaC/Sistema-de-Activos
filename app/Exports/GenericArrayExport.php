<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export genérico reutilizable por cualquier reporte tabular del sistema
 * (Informes, y cualquier otro módulo que en el futuro necesite un botón de
 * "Exportar Excel"), para no crear una clase Export distinta por reporte.
 */
class GenericArrayExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    private array $datos;
    private array $encabezados;

    public function __construct(array $datos, array $encabezados)
    {
        $this->datos = $datos;
        $this->encabezados = $encabezados;
    }

    public function array(): array
    {
        return $this->datos;
    }

    public function headings(): array
    {
        return $this->encabezados;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
