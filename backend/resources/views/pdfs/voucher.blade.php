<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Voucher {{ $remito_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a1a;
            line-height: 1.4;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 20px;
        }

        .logo-section {
            max-width: 150px;
        }

        .logo-section img {
            max-width: 100%;
            height: auto;
        }

        .title-section {
            text-align: right;
            flex: 1;
        }

        .title-section h1 {
            font-size: 28px;
            color: #0066cc;
            margin-bottom: 5px;
        }

        .title-section p {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
            text-transform: uppercase;
        }

        .status-badge.aprobado {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-badge.pendiente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #0066cc;
        }

        .info-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0066cc;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .route-section {
            background-color: #f0f7ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .route-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .route-item:last-child {
            margin-bottom: 0;
        }

        .route-icon {
            font-size: 20px;
            margin-right: 15px;
            color: #0066cc;
            flex-shrink: 0;
        }

        .route-info h3 {
            font-size: 13px;
            color: #1a1a1a;
            margin-bottom: 3px;
        }

        .route-info p {
            font-size: 12px;
            color: #666;
        }

        .amount-section {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: right;
            margin-bottom: 20px;
        }

        .amount-label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .amount-value {
            font-size: 32px;
            font-weight: bold;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .detail-box {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
        }

        .detail-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .detail-value {
            font-size: 13px;
            color: #1a1a1a;
            font-weight: 600;
        }

        .observations {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            min-height: 50px;
        }

        .observations-label {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0066cc;
            margin-bottom: 8px;
        }

        .observations-text {
            font-size: 13px;
            color: #1a1a1a;
            font-style: italic;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .generated-date {
            font-size: 11px;
            color: #999;
            text-align: right;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        tr {
            border-bottom: 1px solid #e0e0e0;
        }

        td {
            padding: 10px 0;
            font-size: 13px;
        }

        .label-col {
            font-weight: bold;
            color: #0066cc;
            width: 35%;
        }

        .value-col {
            color: #1a1a1a;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            @if($logo_empresa)
                <div class="logo-section">
                    <img src="{{ $logo_empresa }}" alt="Logo Empresa">
                </div>
            @endif
            <div class="title-section">
                <h1>VOUCHER</h1>
                <p>Recibo de Viaje</p>
                <div class="status-badge {{ strtolower($status) }}">
                    {{ $status }}
                </div>
            </div>
        </div>

        <!-- Información Principal -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">Número de Remito</div>
                <div class="info-value">{{ $remito_code }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Fecha</div>
                <div class="info-value">{{ $fecha }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Pasajero</div>
                <div class="info-value">{{ $pasajero }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Empresa</div>
                <div class="info-value">{{ $empresa }}</div>
            </div>
        </div>

        <!-- Sección de Ruta -->
        <h3 class="section-title">Itinerario del Viaje</h3>
        <div class="route-section">
            <div class="route-item">
                <div class="route-icon">📍</div>
                <div class="route-info">
                    <h3>Origen</h3>
                    <p><strong>{{ $origen }}</strong></p>
                    <p>Salida: {{ $hora_origen }} hs</p>
                </div>
            </div>
            <div class="route-item">
                <div class="route-icon">→</div>
                <div class="route-info">
                    <h3>Destino</h3>
                    <p><strong>{{ $destino }}</strong></p>
                    <p>Llegada: {{ $hora_destino }} hs</p>
                </div>
            </div>
            @if($tiempo_espera > 0)
                <div class="route-item">
                    <div class="route-icon">⏱</div>
                    <div class="route-info">
                        <h3>Tiempo de Espera</h3>
                        <p><strong>{{ $tiempo_espera }} minutos</strong></p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Importe -->
        <div class="amount-section">
            <div class="amount-label">Importe del Viaje</div>
            <div class="amount-value">$ {{ $monto }}</div>
        </div>

        <!-- Detalles -->
        <div class="details-grid">
            <div class="detail-box">
                <div class="detail-label">Chofer Responsable</div>
                <div class="detail-value">{{ $chofer }}</div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Estado</div>
                <div class="detail-value">{{ $status }}</div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Generado</div>
                <div class="detail-value">{{ date('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Observaciones -->
        @if($observaciones)
            <div class="observations">
                <div class="observations-label">Observaciones</div>
                <div class="observations-text">{{ $observaciones }}</div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Este documento es un comprobante de pago emitido por New Harvest Transportes.</p>
            <p>Para consultas o reclamos, contáctenos a través de nuestro sistema de atención al cliente.</p>
        </div>

        <div class="generated-date">
            Generado el {{ date('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
