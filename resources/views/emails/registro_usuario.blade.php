<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Sistema de Activos</title>
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
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
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
            background-color: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 15px;
            color: #475569;
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
            <h1>Sistema de Gestión de Activos</h1>
        </div>
        <div class="content">
            <div class="greeting">
                {{ $saludo }} {{ $usuario->nombre }} {{ $usuario->apellido }},
            </div>
            
            <div class="message-box">
                Su cuenta será habilitada por el administrador, por favor espere. Se le enviará un correo cuando ya se haya habilitado.
            </div>

            <p>Agradecemos su paciencia mientras nuestro equipo verifica y activa sus permisos de acceso.</p>

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
