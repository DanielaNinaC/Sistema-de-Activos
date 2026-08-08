<?php

namespace App\Services;

use App\Exports\GenericArrayExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Exportación a PDF/Excel reutilizable por cualquier módulo (no exclusivo de
 * Informes) — evita que cada controlador reimplemente la carga de dompdf o
 * la creación de una clase Export por reporte.
 */
class ExportService
{
    public function exportarPdf(string $vistaBlade, array $datos, string $nombreArchivo): Response
    {
        $pdf = Pdf::loadView($vistaBlade, $datos);

        return $pdf->download($nombreArchivo . '.pdf');
    }

    public function exportarExcel(array $datosTabulares, array $encabezados, string $nombreArchivo): BinaryFileResponse
    {
        return Excel::download(new GenericArrayExport($datosTabulares, $encabezados), $nombreArchivo . '.xlsx');
    }
}
