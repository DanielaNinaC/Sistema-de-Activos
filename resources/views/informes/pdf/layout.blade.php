<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo', 'Reporte Institucional') — SIATEC</title>
    <style>
        @page {
            margin: 13mm 12mm 17mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.35;
            background: #ffffff;
        }

        /* ═══════════════════════════════════════════════
           ENCABEZADO INSTITUCIONAL
        ═══════════════════════════════════════════════ */
        .inst-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .inst-header td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .inst-logo-cell {
            width: 20%;
            text-align: left;
        }

        .inst-title-cell {
            width: 60%;
            text-align: center;
        }

        .inst-system-cell {
            width: 20%;
            text-align: right;
        }

        .inst-logo {
            max-height: 52px;
            max-width: 160px;
            object-fit: contain;
            display: inline-block;
        }

        .inst-system-logo {
            max-height: 34px;
            max-width: 120px;
            object-fit: contain;
            display: inline-block;
        }

        .inst-logo-placeholder {
            font-size: 10pt;
            font-weight: bold;
            color: #1e3a5f;
            text-transform: uppercase;
        }

        .inst-nombre-institucion {
            font-size: 8pt;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .inst-titulo-documento {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .inst-subtitulo {
            margin-top: 2px;
            font-size: 8pt;
            color: #475569;
            font-style: italic;
        }

        .inst-meta {
            margin-top: 4px;
            font-size: 8pt;
            color: #334155;
        }

        .inst-meta strong {
            color: #0f172a;
        }

        .inst-meta-sec {
            margin-top: 1px;
            font-size: 7.5pt;
            color: #64748b;
        }

        .inst-divider {
            width: 100%;
            height: 2.5px;
            background-color: #1e3a5f;
            margin: 7px 0 10px 0;
        }

        /* ═══════════════════════════════════════════════
           ESTILOS GENERALES DE CONTENIDO
        ═══════════════════════════════════════════════ */
        h1 { font-size: 13pt; margin: 0 0 2px; color: #0f172a; }

        .subtitulo { font-size: 8pt; color: #64748b; margin-bottom: 12px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: auto;
        }

        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr    { page-break-inside: avoid; page-break-after: auto; }

        th {
            background-color: #1e3a5f;
            color: #ffffff;
            text-align: left;
            padding: 5px 7px;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            border-bottom: 2px solid #0f233d;
            border-left: 0.5pt solid #2c4c77;
            border-right: 0.5pt solid #2c4c77;
            vertical-align: middle;
        }

        td {
            padding: 5px 7px;
            border: 0.5pt solid #e2e8f0;
            font-size: 8pt;
            vertical-align: middle;
            line-height: 1.25;
        }

        tbody tr:nth-child(even)  { background-color: #f8fafc; }
        tbody tr:nth-child(odd)   { background-color: #ffffff; }

        .center { text-align: center; }
        .right  { text-align: right; }

        /* Badges de criticidad / estado */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .badge-critico { background-color: #fef2f2; color: #991b1b; border: 0.5pt solid #fca5a5; }
        .badge-alto    { background-color: #fff7ed; color: #9a3412; border: 0.5pt solid #fed7aa; }
        .badge-medio   { background-color: #fffbeb; color: #92400e; border: 0.5pt solid #fde68a; }
        .badge-bajo    { background-color: #ecfdf5; color: #065f46; border: 0.5pt solid #86efac; }
        .badge-neutral { background-color: #f8fafc;  color: #475569; border: 0.5pt solid #cbd5e1; }

        /* Tarjetas KPI */
        .kpi-grid { width: 100%; margin: 10px 0; }

        .kpi {
            display: inline-block;
            width: 22%;
            margin-right: 1.3%;
            padding: 9px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            text-align: center;
            vertical-align: top;
        }

        .kpi-label {
            font-size: 7pt;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            letter-spacing: 0.3px;
        }

        .kpi-value {
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            display: block;
            margin-top: 2px;
        }

        /* Sección título (h2 interior) */
        .seccion-titulo {
            font-size: 9.5pt;
            font-weight: bold;
            color: #ffffff;
            background-color: #1e3a5f;
            padding: 5px 9px;
            margin-top: 16px;
            margin-bottom: 0;
            border-radius: 3px;
        }

        /* Pie de página (solo aparece en el script DomPDF) */
        .footer {
            margin-top: 20px;
            font-size: 7.5pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
    @yield('estilos')
</head>
<body>

    {{-- ═══════════ ENCABEZADO INSTITUCIONAL ═══════════ --}}
    <table class="inst-header">
        <tr>
            {{-- Logo institucional --}}
            <td class="inst-logo-cell">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo Institucional" class="inst-logo">
                @else
                    <div class="inst-logo-placeholder">Lycée Franco-Bolivien</div>
                @endif
            </td>

            {{-- Título central --}}
            <td class="inst-title-cell">
                <div class="inst-nombre-institucion">Lycée Franco-Bolivien</div>
                <h1 class="inst-titulo-documento">@yield('titulo', 'Reporte Institucional')</h1>
                <div class="inst-subtitulo">@yield('subtitulo')</div>
                <div class="inst-meta">
                    <strong>Responsable:</strong> {{ $responsable ?? 'Sistema SIATEC' }}
                </div>
                <div class="inst-meta-sec">
                    <strong>Fecha:</strong> {{ $fechaGeneracion ?? now()->format('d/m/Y H:i') }}
                </div>
            </td>

            {{-- Logo SIATEC --}}
            <td class="inst-system-cell">
                @if(!empty($siatecBase64))
                    <img src="{{ $siatecBase64 }}" alt="SIATEC" class="inst-system-logo">
                @else
                    <div class="inst-logo-placeholder" style="font-size: 9pt; color: #475569;">SIATEC</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="inst-divider"></div>

    {{-- ═══════════ CONTENIDO DEL INFORME ═══════════ --}}
    @yield('contenido')

    {{-- ═══════════ PIE DE PÁGINA DINÁMICO (DomPDF PHP Script) ═══════════ --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font  = $fontMetrics->getFont("Helvetica", "normal");
            $size  = 7;
            $color = array(0.58, 0.64, 0.71);   // #94a3b8

            $w = $pdf->get_width();
            $h = $pdf->get_height();

            // Línea superior del pie
            $pdf->line(34, $h - 28, $w - 34, $h - 28, array(0.80, 0.83, 0.88), 0.5);

            // Texto izquierdo: institución y sistema
            $leftText = "Lycée Franco-Bolivien \xc2\xb7 SIATEC \xe2\x80\x94 Sistema de Gestión de Activos Tecnológicos";
            $pdf->page_text(34, $h - 20, $leftText, $font, $size, $color);

            // Texto derecho: Página X de Y
            $pageText  = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
            $pageWidth = $fontMetrics->getTextWidth($pageText, $font, $size);
            $pdf->page_text($w - 34 - $pageWidth, $h - 20, $pageText, $font, $size, $color);
        }
    </script>

</body>
</html>
