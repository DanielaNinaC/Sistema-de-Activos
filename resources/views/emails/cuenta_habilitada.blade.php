<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Habilitada - Sistema de Activos</title>
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
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
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
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .message-box {
            background-color: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 16px 20px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 15px;
            color: #065f46;
        }
        .btn-container {
            text-align: center;
            margin: 25px 0;
        }
        .btn {
            display: inline-block;
            background-color: #059669;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.3);
        }
        .contact-box {
            margin-top: 25px;
            padding: 15px;
            background-color: #eff6ff;
            border-radius: 8px;
            font-size: 14px;
            color: #1d4ed8;
            text-align: center;
        }
        .contact-box a {
            color: #1d4ed8;
            font-weight: 600;
            text-decoration: underline;
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
            <h1>¡Cuenta Habilitada!</h1>
        </div>
        <div class="content">
            <div class="greeting">
                {{ $saludo }} {{ $usuario->nombre }} {{ $usuario->apellido }},
            </div>
            
            <div class="message-box">
                Nos complace informarle que su cuenta en el Sistema de Gestión de Activos ha sido activada y habilitada por el administrador.
            </div>

            <p>Ya puede acceder al sistema con sus credenciales de inicio de sesión.</p>

            <div class="btn-container">
                <a href="{{ route('login') }}" class="btn">Iniciar Sesión</a>
            </div>

            <div class="contact-box">
                Cualquier consulta escribir a <a href="mailto:soporte@franco.edu.bo">soporte@franco.edu.bo</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Sistema de Activos Franco. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
