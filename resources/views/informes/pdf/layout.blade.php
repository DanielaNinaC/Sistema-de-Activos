<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo', 'Reporte')</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1e293b; margin: 30px; }
        h1 { font-size: 18px; margin-bottom: 2px; color: #0f172a; }
        .subtitulo { font-size: 10px; color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; border-bottom: 2px solid #cbd5e1; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-critico { background: #fee2e2; color: #991b1b; }
        .badge-alto { background: #ffedd5; color: #9a3412; }
        .badge-medio { background: #fef9c3; color: #854d0e; }
        .badge-bajo { background: #dcfce7; color: #166534; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; text-align: center; }
        .kpi-grid { width: 100%; margin: 16px 0; }
        .kpi { display: inline-block; width: 22%; margin-right: 1.3%; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; vertical-align: top; }
        .kpi-label { font-size: 8px; text-transform: uppercase; color: #64748b; display: block; }
        .kpi-value { font-size: 18px; font-weight: bold; color: #0f172a; display: block; margin-top: 2px; }
        .seccion-titulo { font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 18px; margin-bottom: 4px; }
    </style>
</head>
<body>
    <h1>@yield('titulo', 'Reporte')</h1>
    <p class="subtitulo">@yield('subtitulo') &middot; Generado el {{ now()->format('d/m/Y H:i') }}</p>

    @yield('contenido')

    <div class="footer">Sistema de Activos Tecnol&oacute;gicos &mdash; Documento generado autom&aacute;ticamente</div>
</body>
</html>
