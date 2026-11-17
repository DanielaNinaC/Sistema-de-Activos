<?php

namespace App\Exports\Sheets;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SheetHelper
{
    /**
     * Inserta el logo institucional en la esquina superior izquierda (A1)
     * y el título "LISTADO DE ACTIVOS TECNOLÓGICOS - [subtitulo]" centrado
     * en el rango especificado de la fila 1.
     *
     * @param Worksheet $sheet        La hoja de cálculo activa
     * @param string    $rangoTitulo  Rango para el merge del título (p.ej. 'C1:L1')
     * @param string    $subtitulo    Descripción de la categoría (p.ej. 'PROYECTORES')
     * @param string    $colorFondo   Color de fondo HEX sin # (por defecto azul oscuro '1B365D')
     */
    public static function agregarLogoYTitulo(
        Worksheet $sheet,
        string $rangoTitulo,
        string $subtitulo,
        string $colorFondo = '1B365D'
    ): void {
        // Altura de la fila 1 ampliada para alojar el logo
        $sheet->getRowDimension(1)->setRowHeight(55);

        // ── Logo institucional ─────────────────────────────────────────────
        $logoPath = public_path('imagenes/logoFranco.jpg');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo Institucional');
            $drawing->setDescription('Lycée Franco-Bolivien');
            $drawing->setPath($logoPath);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(4);
            $drawing->setOffsetY(4);
            $drawing->setHeight(48);
            $drawing->setWorksheet($sheet);
        }

        // ── Título centrado ────────────────────────────────────────────────
        $sheet->mergeCells($rangoTitulo);
        $textoTitulo = 'LISTADO DE ACTIVOS TECNOLÓGICOS';
        if (!empty($subtitulo)) {
            $textoTitulo .= ' - ' . mb_strtoupper($subtitulo);
        }
        $sheet->setCellValue(explode(':', $rangoTitulo)[0], $textoTitulo);
        $sheet->getStyle($rangoTitulo)->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorFondo],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => false,
            ],
        ]);

        // Colorear también A1 y B1 con el mismo fondo para unificar la barra
        [$startCell] = explode(':', $rangoTitulo);
        // Detectar columna de inicio del rango del título
        preg_match('/^([A-Z]+)/', $startCell, $m);
        $colInicioTitulo = $m[1] ?? 'C';

        // Colorear A1 y B1 con el mismo fondo (zona del logo)
        $sheet->getStyle('A1')->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorFondo],
            ],
        ]);
        if ($colInicioTitulo > 'B') {
            $sheet->getStyle('B1')->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $colorFondo],
                ],
            ]);
        }
    }

    /**
     * Mapea el estado operativo textual del activo a 1, 2 o 3:
     * 1 = Buen Estado / Operativo
     * 2 = Equipo Antiguo / Regular / En Mantenimiento
     * 3 = Cambio Inmediato / Inoperativo / De Baja
     */
    public static function mapearEstado(?string $estado): int
    {
        $est = strtolower(trim($estado ?? ''));
        if (str_contains($est, 'inoperativo') || str_contains($est, 'baja') || str_contains($est, 'cambio') || str_contains($est, 'dañad') || str_contains($est, 'critico')) {
            return 3;
        }
        if (str_contains($est, 'mantenimiento') || str_contains($est, 'regular') || str_contains($est, 'antiguo') || str_contains($est, 'revision') || str_contains($est, 'medio')) {
            return 2;
        }
        return 1;
    }

    /**
     * Aplica bordes negros delgados a un rango de celdas.
     */
    public static function aplicarBordes(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    /**
     * Aplica estilo al encabezado principal de la pestaña.
     */
    public static function aplicarTitulo(Worksheet $sheet, string $rango, string $colorFondo = '1B365D', string $colorTexto = 'FFFFFF'): void
    {
        $sheet->mergeCells($rango);
        $sheet->getStyle($rango)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => $colorTexto],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorFondo],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);
    }

    /**
     * Aplica estilo a los encabezados de columnas de la tabla.
     */
    public static function aplicarEncabezadosTabla(Worksheet $sheet, string $rango, string $colorFondo = '2F4F4F'): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorFondo],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(28);
    }

    /**
     * Genera la tabla resumen semáforo en las columnas indicadas.
     * Ejemplo en columnas K, L, M a partir de la fila 4:
     * Fila 4: BUEN ESTADO | 1 | =CONTAR.SI(H4:H100, 1) (#00B050)
     * Fila 5: EQUIPO ANTIGUO | 2 | =CONTAR.SI(H4:H100, 2) (#FFFF00)
     * Fila 6: CAMBIO INMEDIATO | 3 | =CONTAR.SI(H4:H100, 3) (#FF0000)
     */
    public static function crearTablaSemaforo(
        Worksheet $sheet,
        string $colTexto,
        string $colNum,
        string $colFormula,
        string $colEstadoDatos,
        int $filaInicioDatos,
        int $filaFinDatos
    ): void {
        // Encabezado del semáforo
        $rangoTitulo = "{$colTexto}3:{$colFormula}3";
        $sheet->mergeCells($rangoTitulo);
        $sheet->setCellValue("{$colTexto}3", 'RESUMEN DE ESTADO');
        $sheet->getStyle($rangoTitulo)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B365D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $maxRow = max($filaFinDatos, $filaInicioDatos);
        $rangoRango = "{$colEstadoDatos}{$filaInicioDatos}:{$colEstadoDatos}{$maxRow}";

        // Fila 4: BUEN ESTADO (Verde #00B050)
        $sheet->setCellValue("{$colTexto}4", 'BUEN ESTADO');
        $sheet->setCellValue("{$colNum}4", 1);
        $sheet->setCellValue("{$colFormula}4", "=COUNTIF({$rangoRango}, 1)");
        $sheet->getStyle("{$colTexto}4:{$colFormula}4")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B050']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("{$colTexto}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Fila 5: EQUIPO ANTIGUO (Amarillo #FFFF00)
        $sheet->setCellValue("{$colTexto}5", 'EQUIPO ANTIGUO');
        $sheet->setCellValue("{$colNum}5", 2);
        $sheet->setCellValue("{$colFormula}5", "=COUNTIF({$rangoRango}, 2)");
        $sheet->getStyle("{$colTexto}5:{$colFormula}5")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("{$colTexto}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Fila 6: CAMBIO INMEDIATO (Rojo #FF0000)
        $sheet->setCellValue("{$colTexto}6", 'CAMBIO INMEDIATO');
        $sheet->setCellValue("{$colNum}6", 3);
        $sheet->setCellValue("{$colFormula}6", "=COUNTIF({$rangoRango}, 3)");
        $sheet->getStyle("{$colTexto}6:{$colFormula}6")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("{$colTexto}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Bordes de la tabla semáforo
        self::aplicarBordes($sheet, "{$colTexto}3:{$colFormula}6");
    }

    /**
     * Aplica formato condicional y coloreado a la columna de estado.
     */
    public static function aplicarFormatoCondicionalEstado(Worksheet $sheet, string $colEstado, int $filaInicio, int $filaFin): void
    {
        if ($filaFin < $filaInicio) {
            return;
        }

        $rango = "{$colEstado}{$filaInicio}:{$colEstado}{$filaFin}";

        // Coloreado directo celda a celda para máxima compatibilidad
        for ($r = $filaInicio; $r <= $filaFin; $r++) {
            $val = (int) $sheet->getCell("{$colEstado}{$r}")->getValue();
            $colorFondo = match ($val) {
                1 => '00B050',
                2 => 'FFFF00',
                3 => 'FF0000',
                default => 'E2E8F0',
            };
            $colorTexto = match ($val) {
                1, 3 => 'FFFFFF',
                default => '000000',
            };

            $sheet->getStyle("{$colEstado}{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $colorTexto]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorFondo]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Reglas de formato condicional de Excel
        $cond1 = new Conditional();
        $cond1->setConditionType(Conditional::CONDITION_CELLIS);
        $cond1->setOperatorType(Conditional::OPERATOR_EQUAL);
        $cond1->addCondition('1');
        $cond1->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF00B050');
        $cond1->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
        $cond1->getStyle()->getFont()->setBold(true);

        $cond2 = new Conditional();
        $cond2->setConditionType(Conditional::CONDITION_CELLIS);
        $cond2->setOperatorType(Conditional::OPERATOR_EQUAL);
        $cond2->addCondition('2');
        $cond2->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');
        $cond2->getStyle()->getFont()->getColor()->setARGB('FF000000');
        $cond2->getStyle()->getFont()->setBold(true);

        $cond3 = new Conditional();
        $cond3->setConditionType(Conditional::CONDITION_CELLIS);
        $cond3->setOperatorType(Conditional::OPERATOR_EQUAL);
        $cond3->addCondition('3');
        $cond3->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
        $cond3->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
        $cond3->getStyle()->getFont()->setBold(true);

        $conditionalStyles = $sheet->getStyle($rango)->getConditionalStyles();
        $conditionalStyles[] = $cond1;
        $conditionalStyles[] = $cond2;
        $conditionalStyles[] = $cond3;
        $sheet->getStyle($rango)->setConditionalStyles($conditionalStyles);
    }
}
