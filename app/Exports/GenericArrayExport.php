<?php

namespace App\Exports;

use App\Exports\Sheets\SheetHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export genérico institucional reutilizable por cualquier reporte tabular.
 *
 * Genera un libro de trabajo con:
 *  - Fila 1: encabezado institucional con logo (izquierda) + título LISTADO DE ACTIVOS TECNOLÓGICOS (centro)
 *  - Fila 2: metadatos del reporte (responsable, fecha, total de registros)
 *  - Fila 3: encabezados de columnas con fondo azul oscuro y texto blanco
 *  - Filas 4+: datos con alternancia de colores, bordes y alineaciones
 *
 * Acepta opcionalmente un subtítulo / nombre de reporte para personalizar el encabezado.
 */
class GenericArrayExport implements FromArray, WithCustomStartCell, WithStyles, WithTitle
{
    private array  $datos;
    private array  $encabezados;
    private string $nombreReporte;
    private string $responsable;
    private string $fechaGeneracion;
    private int    $totalRegistros;

    /**
     * @param array  $datos          Filas de datos (arrays simples)
     * @param array  $encabezados    Etiquetas de columnas
     * @param string $nombreReporte  Nombre corto del reporte (ej. 'Vida Útil Remanente')
     */
    public function __construct(array $datos, array $encabezados, string $nombreReporte = '')
    {
        $this->datos          = $datos;
        $this->encabezados    = $encabezados;
        $this->nombreReporte  = $nombreReporte;
        $usuario = auth()->user();
        $this->responsable    = $usuario
            ? trim(($usuario->nombre ?? '') . ' ' . ($usuario->apellido ?? '')) ?: 'Sistema SIATEC'
            : 'Sistema SIATEC';
        $this->fechaGeneracion = now()->format('d/m/Y H:i');
        $this->totalRegistros = count($datos);
    }

    public function title(): string
    {
        if (empty($this->nombreReporte)) {
            return 'REPORTE';
        }
        $limpio = preg_replace('/[\\\\\/\*\?\:\[\]]/', '', $this->nombreReporte);
        return mb_substr(strtoupper($limpio), 0, 30);
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function array(): array
    {
        // Fila 3 (startCell = A3): encabezados de columnas
        // Filas 4+: datos
        $filas = [$this->encabezados];
        foreach ($this->datos as $fila) {
            $filas[] = array_values((array) $fila);
        }
        return $filas;
    }

    public function styles(Worksheet $sheet)
    {
        $numColumnas = count($this->encabezados);
        // Letra de la última columna (A=0, B=1, … Z=25)
        $ultimaCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numColumnas);

        // ── 1. ENCABEZADO INSTITUCIONAL (Fila 1) ─────────────────────────────
        SheetHelper::agregarLogoYTitulo(
            $sheet,
            "C1:{$ultimaCol}1",
            $this->nombreReporte,
            '1B365D'
        );

        // ── 2. FILA DE METADATOS (Fila 2) ────────────────────────────────────
        $sheet->mergeCells("A2:{$ultimaCol}2");
        $metaTexto = "Responsable: {$this->responsable}     |     Fecha: {$this->fechaGeneracion}     |     Total de registros: {$this->totalRegistros}";
        $sheet->setCellValue('A2', $metaTexto);
        $sheet->getStyle("A2:{$ultimaCol}2")->applyFromArray([
            'font'      => ['size' => 8.5, 'italic' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C4F7E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── 3. ENCABEZADOS DE TABLA (Fila 3) ─────────────────────────────────
        SheetHelper::aplicarEncabezadosTabla($sheet, "A3:{$ultimaCol}3", '1B365D');

        // ── 4. DATOS (Fila 4 en adelante) ────────────────────────────────────
        $filaInicio = 4;
        $filaFin    = max(3 + $this->totalRegistros, 4);

        if ($this->totalRegistros > 0) {
            // Bordes completos
            SheetHelper::aplicarBordes($sheet, "A3:{$ultimaCol}{$filaFin}");

            // Alternancia de colores por fila
            for ($fila = $filaInicio; $fila <= $filaFin; $fila++) {
                $colorFondo = ($fila % 2 === 0) ? 'EBF0F8' : 'FFFFFF';
                $sheet->getStyle("A{$fila}:{$ultimaCol}{$fila}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($colorFondo);
            }

            // Alineación general de datos: centrado vertical, texto con wrap en todas
            $sheet->getStyle("A{$filaInicio}:{$ultimaCol}{$filaFin}")->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            // Primera columna generalmente es N.º o Código → centrar horizontalmente
            $sheet->getStyle("A{$filaInicio}:A{$filaFin}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            SheetHelper::aplicarBordes($sheet, "A3:{$ultimaCol}3");
        }

        // ── 5. LÍNEA SEPARADORA debajo de los encabezados ────────────────────
        $sheet->getStyle("A3:{$ultimaCol}3")->getBorders()->getBottom()->applyFromArray([
            'borderStyle' => Border::BORDER_MEDIUM,
            'color'       => ['rgb' => '0F2A4A'],
        ]);

        // ── 6. FILA DE TOTAL AL PIE (si hay datos) ───────────────────────────
        if ($this->totalRegistros > 0) {
            $filaTotal = $filaFin + 1;
            $sheet->mergeCells("A{$filaTotal}:{$ultimaCol}{$filaTotal}");
            $sheet->setCellValue("A{$filaTotal}", "Total de registros: {$this->totalRegistros}");
            $sheet->getStyle("A{$filaTotal}:{$ultimaCol}{$filaTotal}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B365D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$filaTotal}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($filaTotal)->setRowHeight(18);
        }

        return [];
    }
}
