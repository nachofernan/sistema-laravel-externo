<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Solicitud de Registro de Proveedores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-size: 0.9em;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #dcdcdc;
        }
        .header {
            background-color: #002E80;
            padding: 20px 10px;
            text-align: center;
        }
        .header img {
            width: 223px;
            height: 50px;
        }
        .content {
            padding: 20px;
        }
        .footer {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
            font-size: 10px;
            color: #666666;
        }
        p {
            padding: 0px 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="http://ccasa.com.ar/img/logo-blanco.png" alt="Logo de la Empresa">
        </div>
        <div class="content">
            <h2>Formulario de Solicitud de Registro de Proveedores</h2>
            <strong>Fecha:</strong>{{\Carbon\Carbon::now()->format('d/m/Y')}}<hr>

            <strong>Nombre Completo:</strong>{{$datos['nombre_y_apellido']}}<br>
            <strong>Tipo y Número de Documento:</strong>{{$datos['tipo']}} {{$datos['numero']}}<br>
            <strong>Correo Electrónico Personal:</strong>{{$datos['correo_personal']}}<br>
            <hr>
            <strong>Razón social:</strong>{{$datos['razonsocial']}}<br>
            <strong>CUIT/CUIL:</strong>{{$datos['cuit']}}<br>
            <strong>Correo Electrónico Institucional:</strong>{{$datos['correo_institucional']}}<br>
            <strong>Teléfono:</strong>{{$datos['telefono']}}<br>
            <strong>Dirección:</strong>{{$datos['direccion']}}<br>
            <hr>
            Próximamente Centrales de la Costa Atlántica S.A. se contactará con usted para ampliar la solicitud de la información.
            <hr>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático, por favor, no responda a este correo.</p>
            <p>&copy; {{\Carbon\Carbon::now()->format('Y')}} | CCASA | Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>