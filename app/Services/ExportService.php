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
        $institucionales = $this->buildDatosInstitucionales();

        // Si se envió un auditor o responsable explícito en $datos, prevalece
        if (!empty($datos['auditor'])) {
            $institucionales['responsable'] = $datos['auditor'];
        } elseif (!empty($datos['responsable'])) {
            $institucionales['responsable'] = $datos['responsable'];
        }

        // Inyectar variables institucionales comunes a todos los PDF
        $datos = array_merge($institucionales, $datos);

        $pdf = Pdf::loadView($vistaBlade, $datos)
            ->setOption(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download($nombreArchivo . '.pdf');
    }

    /**
     * Genera las variables comunes del encabezado institucional:
     * logo base64, logo SIATEC base64, nombre del responsable/auditor y fecha de generación.
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

        // Auditor / Responsable editable enviado por request (GET o POST)
        $auditorParam = request()->input('auditor') ?: request()->input('responsable');
        if (!empty($auditorParam)) {
            $nombreResponsable = trim($auditorParam);
        } else {
            $usuario = auth()->user();
            $nombreUsuario = $usuario
                ? trim(($usuario->nombre ?? '') . ' ' . ($usuario->apellido ?? ''))
                : '';

            // Por requerimiento institucional: no mostrar 'Daniela Nina', usar 'Carlos Apaza'
            if (empty($nombreUsuario) || stripos($nombreUsuario, 'Daniela') !== false) {
                $nombreResponsable = 'Carlos Apaza';
            } else {
                $nombreResponsable = $nombreUsuario;
            }
        }

        return [
            'logoBase64'      => $logoBase64,
            'siatecBase64'    => $siatecBase64,
            'responsable'     => $nombreResponsable ?: 'Carlos Apaza',
            'auditor'         => $nombreResponsable ?: 'Carlos Apaza',
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
