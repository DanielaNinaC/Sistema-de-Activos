<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Riesgo Crítico - Sistema de Activos</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            color: #334155;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
            padding: 25px 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 30px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .message-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 16px 20px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 15px;
            color: #7f1d1d;
        }
        .datos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        .datos-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .datos-table td:first-child {
            font-weight: 600;
            color: #64748b;
            width: 40%;
        }
        .datos-table td:last-child {
            color: #0f172a;
            font-weight: 600;
        }
        .btn-container {
            text-align: center;
            margin: 25px 0;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>⚠️ Alerta de Riesgo Crítico</h1>
        </div>
        <div class="content">
            <div class="greeting">
                Se generó una nueva evaluación de riesgo con nivel <strong>Crítico</strong> en el Sistema de Activos Tecnológicos.
            </div>

            <div class="message-box">
                Este activo requiere atención inmediata: se sugiere priorizar el tratamiento y, si corresponde, programar el mantenimiento correspondiente.
            </div>

            <table class="datos-table">
                <tr>
                    <td>Activo</td>
                    <td>{{ $activo->nombre_equipo ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Código Patrimonial</td>
                    <td>{{ $activo->codigo_patrimonial ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Categoría</td>
                    <td>{{ $activo->categoria->nombre_categoria ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Modo de Falla</td>
                    <td>{{ $modoFalla->nombre_modo_falla ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Score Calculado</td>
                    <td>{{ $evaluacion->score_calculado ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Nivel de Riesgo</td>
                    <td>{{ $evaluacion->nivel_riesgo ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Fecha de Evaluación</td>
                    <td>{{ $evaluacion?->fecha_evaluacion ? \Carbon\Carbon::parse($evaluacion->fecha_evaluacion)->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            </table>

            @if($activo)
                <div class="btn-container">
                    <a href="{{ route('admin.activos.show', $activo->id_activo) }}" class="btn">Ver Activo en el Sistema</a>
                </div>
            @endif
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Sistema de Activos Franco. Notificación automática — no responder a este correo.
        </div>
    </div>
</body>
</html>
