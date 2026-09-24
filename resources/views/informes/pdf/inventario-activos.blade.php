<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Activos Tecnológicos</title>
    <style>
        @page {
            margin: 12mm 12mm 16mm 12mm;
            size: letter landscape;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.3;
            background: #ffffff;
        }

        /* ================= ENCABEZADO INSTITUCIONAL ================= */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .header-logo-cell {
            width: 22%;
            text-align: left;
        }

        .header-title-cell {
            width: 56%;
            text-align: center;
        }

        .header-system-cell {
            width: 22%;
            text-align: right;
        }

        .header-logo {
            max-height: 54px;
            max-width: 170px;
            width: auto;
            object-fit: contain;
            display: inline-block;
        }

        .system-logo {
            max-height: 36px;
            max-width: 130px;
            width: auto;
            object-fit: contain;
            display: inline-block;
        }

        .logo-placeholder {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .institution-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 3px;
        }

        .document-title {
            margin: 0;
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .meta-container {
            margin-top: 4px;
            font-size: 8.5pt;
            color: #334155;
        }

        .meta-responsable {
            font-weight: normal;
        }

        .meta-responsable strong {
            font-weight: bold;
            color: #0f172a;
        }

        .meta-secondary {
            margin-top: 2px;
            font-size: 7.5pt;
            color: #64748b;
        }

        .meta-bullet {
            margin: 0 4px;
            color: #94a3b8;
        }

        .header-divider {
            width: 100%;
            height: 2px;
            background-color: #1e3a5f;
            margin: 8px 0 10px 0;
            border-bottom: 0.5pt solid #cbd5e1;
        }

        /* ================= TABLA DE ACTIVOS ================= */
        .tabla-activos {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
        }

        .tabla-activos thead {
            display: table-header-group;
        }

        .tabla-activos tfoot {
            display: table-footer-group;
        }

        .tabla-activos tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .tabla-activos th {
            background-color: #1e3a5f;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 4px;
            border-top: 1px solid #1e3a5f;
            border-bottom: 2px solid #0f233d;
            border-left: 0.5pt solid #2c4c77;
            border-right: 0.5pt solid #2c4c77;
            text-align: left;
            vertical-align: middle;
        }

        .tabla-activos th.center {
            text-align: center;
        }

        .tabla-activos td {
            padding: 5px 4px;
            border: 0.5pt solid #e2e8f0;
            font-size: 7.5pt;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
            line-height: 1.25;
        }

        .tabla-activos tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .tabla-activos tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .center {
            text-align: center;
        }

        .col-num {
            font-weight: bold;
            color: #64748b;
        }

        .col-codigo {
            font-family: 'Courier', monospace;
            font-weight: bold;
            color: #0f172a;
            font-size: 7.5pt;
        }

        .col-equipo {
            font-weight: 600;
            color: #0f172a;
        }

        .col-ubicacion {
            color: #334155;
        }

        /* ================= BADGES ================= */
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 6.8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
            line-height: 1.2;
            text-align: center;
        }

        /* Criticidad */
        .badge-bajo {
            background-color: #ecfdf5;
            color: #065f46;
            border: 0.5pt solid #86efac;
        }

        .badge-medio {
            background-color: #fffbeb;
            color: #92400e;
            border: 0.5pt solid #fde68a;
        }

        .badge-alto {
            background-color: #fff7ed;
            color: #9a3412;
            border: 0.5pt solid #fed7aa;
        }

        .badge-critico {
            background-color: #fef2f2;
            color: #991b1b;
            border: 0.5pt solid #fca5a5;
        }

        .badge-neutral {
            background-color: #f8fafc;
            color: #475569;
            border: 0.5pt solid #cbd5e1;
        }

        /* Estado Operativo */
        .badge-estado-operativo {
            background-color: #f0fdf4;
            color: #15803d;
            border: 0.5pt solid #bbf7d0;
        }

        .badge-estado-mantenimiento {
            background-color: #fffbeb;
            color: #b45309;
            border: 0.5pt solid #fde68a;
        }

        .badge-estado-inoperativo {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 0.5pt solid #fecaca;
        }

        .badge-estado-baja {
            background-color: #f1f5f9;
            color: #475569;
            border: 0.5pt solid #cbd5e1;
        }
    </style>
</head>
<body>

    {{-- ================= ENCABEZADO DEL DOCUMENTO ================= --}}
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo Institucional" class="header-logo">
                @else
                    <div class="logo-placeholder">Lycée Franco-Bolivien</div>
                @endif
            </td>
            <td class="header-title-cell">
                <div class="institution-name">Lycée Franco-Bolivien</div>
                <h1 class="document-title">LISTA DE ACTIVOS TECNOLÓGICOS</h1>
                <div class="meta-container">
                    <div class="meta-responsable">
                        <strong>Responsable:</strong> {{ $responsable ?? 'Responsable de Activos Tecnológicos' }}
                    </div>
                    <div class="meta-secondary">
                        <span><strong>Fecha y hora:</strong> {{ $fechaGeneracion ?? now()->format('d/m/Y H:i') }}</span>
                        @if(!empty($buscar))
                            <span class="meta-bullet">&bull;</span>
                            <span><strong>Filtro aplicado:</strong> "{{ $buscar }}"</span>
                        @endif
                        <span class="meta-bullet">&bull;</span>
                        <span><strong>Total:</strong> {{ $totalActivos ?? count($listaActivos) }} {{ ($totalActivos ?? count($listaActivos)) == 1 ? 'activo registrado' : 'activos registrados' }}</span>
                    </div>
                </div>
            </td>
            <td class="header-system-cell">
                @if(!empty($siatecBase64))
                    <img src="{{ $siatecBase64 }}" alt="SIATEC" class="system-logo">
                @else
                    <div class="logo-placeholder" style="font-size: 9pt; color: #475569;">SIATEC</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    {{-- ================= TABLA DE ACTIVOS ================= --}}
    <table class="tabla-activos">
        <thead>
            <tr>
                <th class="center" style="width: 4%;">N.º</th>
                <th class="center" style="width: 9.5%;">Código</th>
                <th style="width: 12%;">Tipo</th>
                <th style="width: 20%;">Equipo / Descripción</th>
                <th style="width: 10%;">Marca</th>
                <th style="width: 11%;">Modelo</th>
                <th style="width: 15.5%;">Ubicación</th>
                <th class="center" style="width: 9%;">Estado</th>
                <th class="center" style="width: 9%;">Criticidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($listaActivos as $activo)
                @php
                    // Mapeo de clase de Criticidad (Bajo, Medio, Alto, Crítico)
                    $crit = mb_strtolower(trim($activo['criticidad'] ?? ''));
                    $critClass = match(true) {
                        str_contains($crit, 'crític') || str_contains($crit, 'critic') => 'badge-critico',
                        str_contains($crit, 'alto') || str_contains($crit, 'alta') => 'badge-alto',
                        str_contains($crit, 'medio') || str_contains($crit, 'media') => 'badge-medio',
                        str_contains($crit, 'bajo') || str_contains($crit, 'baja') => 'badge-bajo',
                        default => 'badge-neutral',
                    };

                    // Mapeo de clase de Estado Operativo
                    $est = mb_strtolower(trim($activo['estado_operativo'] ?? ''));
                    $estClass = match(true) {
                        str_contains($est, 'operativo') && !str_contains($est, 'inoperativo') => 'badge-estado-operativo',
                        str_contains($est, 'mantenimiento') => 'badge-estado-mantenimiento',
                        str_contains($est, 'inoperativo') => 'badge-estado-inoperativo',
                        str_contains($est, 'baja') => 'badge-estado-baja',
                        default => 'badge-neutral',
                    };
                @endphp
                <tr>
                    <td class="center col-num">{{ $activo['numero'] ?? $loop->iteration }}</td>
                    <td class="center col-codigo">{{ $activo['codigo_patrimonial'] }}</td>
                    <td>{{ $activo['categoria'] }}</td>
                    <td class="col-equipo">{{ $activo['nombre_equipo'] }}</td>
                    <td>{{ $activo['marca'] }}</td>
                    <td>{{ $activo['modelo'] }}</td>
                    <td class="col-ubicacion">{{ $activo['ubicacion'] }}</td>
                    <td class="center">
                        <span class="badge {{ $estClass }}">{{ $activo['estado_operativo'] }}</span>
                    </td>
                    <td class="center">
                        <span class="badge {{ $critClass }}">{{ $activo['criticidad'] }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center" style="padding: 24px; color: #64748b; font-style: italic;">
                        No se encontraron activos tecnológicos registrados para los criterios seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ================= PIE DE PÁGINA (SCRIPT DOMPDF) ================= --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $boldFont = $fontMetrics->getFont("Helvetica", "bold");
            $size = 7.5;
            $color = array(0.39, 0.45, 0.54); // #64748b sobrio
            
            $width = $pdf->get_width();
            $height = $pdf->get_height();

            // Línea divisoria superior del pie de página
            $pdf->line(34, $height - 28, $width - 34, $height - 28, array(0.80, 0.83, 0.88), 0.5);

            // Texto izquierdo: Nombre institucional y del sistema
            $leftText = "Lycée Franco-Bolivien • SIATEC — Sistema de Gestión de Activos Tecnológicos";
            $pdf->page_text(34, $height - 20, $leftText, $font, $size, $color);

            // Texto derecho: Número de página (Página X de Y)
            $pageText = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
            $pageWidth = $fontMetrics->getTextWidth($pageText, $font, $size);
            $pdf->page_text($width - 34 - $pageWidth, $height - 20, $pageText, $font, $size, $color);
        }
    </script>

</body>
</html>
