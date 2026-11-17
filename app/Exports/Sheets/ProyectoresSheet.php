<?php

namespace App\Exports\Sheets;

use App\Models\ActivoTecnologico;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProyectoresSheet implements FromCollection, WithCustomStartCell, WithStyles, WithTitle
{
    private Collection $activos;
    private int $totalFilas = 0;

    public function __construct(Collection $activos)
    {
        $this->activos = $activos;
    }

    public function title(): string
    {
        return 'PROYECTORES';
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function collection(): Collection
    {
        $filas = [];
        // Fila 3: Encabezados de columnas
        $filas[] = [
            'Nº',
            'AULA',
            'MARCA',
            'MODELO',
            'CODIGO',
            'AÑO DE COMPRA',
            'CABLES',
            'ESTADO PROYECTOR',
            'OBSERVACIONES 1-10',
        ];

        $i = 1;
        foreach ($this->activos as $activo) {
            $aula = $activo->ubicacion->aula ?? $activo->ubicacion->seccion ?? 'S/A';
            $marca = $activo->marca ?? '-';
            $modelo = $activo->modelo ?? '-';
            $codigo = $activo->codigo_patrimonial;
            $anio = $activo->fecha_adquisicion ? date('Y', strtotime($activo->fecha_adquisicion)) : '-';
            $cables = $activo->detalleProyector->tipo_cable ?? 'HDMI / VGA';
            $estadoNum = SheetHelper::mapearEstado($activo->estado_operativo);

            $obs = $activo->observaciones ?? '';
            if (!empty($activo->detalleProyector->calidad_imagen)) {
                $obs = 'Calidad imagen: ' . $activo->detalleProyector->calidad_imagen . '/10' . ($obs ? ' - ' . $obs : '');
            }
            if (empty($obs)) {
                $obs = 'Sin observaciones adicionales';
            }

            $filas[] = [
                $i++,
                $aula,
                $marca,
                $modelo,
                $codigo,
                $anio,
                $cables,
                $estadoNum,
                $obs,
            ];
        }

        $this->totalFilas = count($filas);
        return collect($filas);
    }

    public function styles(Worksheet $sheet)
    {
        // 1. Encabezado General con logo institucional y título institucional
        SheetHelper::agregarLogoYTitulo($sheet, 'C1:I1', 'PROYECTORES', '1B365D');

        // 2. Encabezados de la tabla (Fila 3)
        SheetHelper::aplicarEncabezadosTabla($sheet, 'A3:I3', '1B365D');

        $filaInicio = 4;
        $filaFin = max(3 + count($this->activos), 4);

        if ($this->activos->count() > 0) {
            // Bordes en toda la tabla de datos
            SheetHelper::aplicarBordes($sheet, "A3:I{$filaFin}");

            // Alineaciones
            // [A] Nº - centrado
            $sheet->getStyle("A{$filaInicio}:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [B] AULA - centrado
            $sheet->getStyle("B{$filaInicio}:B{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [C] MARCA - centrado
            $sheet->getStyle("C{$filaInicio}:C{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [D] MODELO - centrado
            $sheet->getStyle("D{$filaInicio}:D{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [E] CODIGO - centrado
            $sheet->getStyle("E{$filaInicio}:E{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [F] AÑO DE COMPRA - centrado
            $sheet->getStyle("F{$filaInicio}:F{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [G] CABLES - centrado
            $sheet->getStyle("G{$filaInicio}:G{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [H] ESTADO PROYECTOR - centrado
            $sheet->getStyle("H{$filaInicio}:H{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            // [I] OBSERVACIONES - izquierda con wrapText
            $sheet->getStyle("I{$filaInicio}:I{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            // Formato Condicional y colores para la columna H
            SheetHelper::aplicarFormatoCondicionalEstado($sheet, 'H', $filaInicio, $filaFin);
        } else {
            // Si no hay datos, borde a encabezado
            SheetHelper::aplicarBordes($sheet, 'A3:I3');
        }

        // 3. Tabla Resumen / Semáforo en columnas K, L, M a partir de fila 4
        // =CONTAR.SI(H4:H100; 1)
        SheetHelper::crearTablaSemaforo($sheet, 'K', 'L', 'M', 'H', $filaInicio, max($filaFin, 100));

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(35);
        $sheet->getColumnDimension('J')->setWidth(4);
        $sheet->getColumnDimension('K')->setWidth(22);
        $sheet->getColumnDimension('L')->setWidth(8);
        $sheet->getColumnDimension('M')->setWidth(14);

        return [];
    }
}
