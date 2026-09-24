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
 *
 * exportarPdf inyecta automáticamente logoBase64, siatecBase64, responsable y
 * fechaGeneracion en todos los PDF para que el layout institucional los reciba.
 */
class ExportService
{
    public function exportarPdf(string $vistaBlade, array $datos, string $nombreArchivo): Response
    {
        // Inyectar variables institucionales comunes a todos los PDF
        $datos = array_merge($this->buildDatosInstitucionales(), $datos);

        $pdf = Pdf::loadView($vistaBlade, $datos)
            ->setOption(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download($nombreArchivo . '.pdf');
    }

    /**
     * Genera las variables comunes del encabezado institucional:
     * logo base64, logo SIATEC base64, nombre del responsable y fecha de generación.
     */
    public function buildDatosInstitucionales(): array
    {
        $logoBase64 = null;
        $logoPath = public_path('imagenes/logoFranco.jpg');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $siatecBase64 = null;
        $siatecPath = public_path('imagenes/siatec.png');
        if (file_exists($siatecPath)) {
            $siatecBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($siatecPath));
        }

        $usuario = auth()->user();
        $nombreResponsable = $usuario
            ? trim(($usuario->nombre ?? '') . ' ' . ($usuario->apellido ?? ''))
            : 'Sistema SIATEC';

        return [
            'logoBase64'      => $logoBase64,
            'siatecBase64'    => $siatecBase64,
            'responsable'     => $nombreResponsable ?: 'Sistema SIATEC',
            'fechaGeneracion' => now()->format('d/m/Y H:i'),
        ];
    }

    public function exportarExcel(
        array $datosTabulares,
        array $encabezados,
        string $nombreArchivo,
        string $tituloReporte = ''
    ): BinaryFileResponse {
        // Usar el nombreArchivo como título de reporte si no se proporciona uno explícito
        $titulo = $tituloReporte ?: ucwords(str_replace(['-', '_'], ' ', $nombreArchivo));

        return Excel::download(
            new GenericArrayExport($datosTabulares, $encabezados, $titulo),
            $nombreArchivo . '.xlsx'
        );
    }
}
