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

        @page {
            margin: 0;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1a1a1a;
        }

        .page {
            padding: 40px 55px;
        }

        /* ===== Header ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 22%;
        }

        .logo-cell img {
            max-height: 55px;
            max-width: 160px;
        }

        .title-cell {
            width: 56%;
            text-align: center;
        }

        .title-cell h1 {
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #1a1a1a;
        }

        .voucher-number {
            font-size: 13px;
            margin-top: 2px;
            color: #1a1a1a;
        }

        .date-label {
            font-size: 10px;
            color: #555;
            margin-top: 8px;
            margin-bottom: 4px;
        }

        .date-boxes {
            display: inline-block;
        }

        .date-box {
            display: inline-block;
            border: 1px solid #999;
            border-radius: 3px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: bold;
            margin: 0 2px;
        }

        .company-cell {
            width: 22%;
            text-align: right;
        }

        .company-cell img {
            max-height: 55px;
            max-width: 160px;
        }

        .divider {
            border-bottom: 2px solid #1a1a1a;
            margin: 28px 0 42px 0;
        }

        /* ===== Cuerpo: datos + firma ===== */
        .body-table {
            width: 100%;
            border-collapse: collapse;
        }

        .body-table td {
            vertical-align: top;
        }

        .data-col {
            width: 62%;
        }

        .signature-col {
            width: 38%;
            text-align: center;
        }

        .data-row {
            display: table;
            width: 100%;
            margin-bottom: 22px;
        }

        .data-row-inner {
            display: table-row;
        }

        .data-label {
            display: table-cell;
            background-color: #1a1a1a;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 13px 16px;
            width: 160px;
            white-space: nowrap;
        }

        .data-value {
            display: table-cell;
            font-size: 15px;
            font-weight: 600;
            padding: 13px 0 13px 18px;
            vertical-align: middle;
        }

        .signature-passenger-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .signature-passenger-img {
            max-width: 180px;
            max-height: 70px;
        }

        .signature-placeholder {
            border-bottom: 1px solid #999;
            width: 200px;
            height: 80px;
            margin: 10px auto 0 auto;
        }

        /* ===== Footer: monto + firma ===== */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 85px;
        }

        .footer-table td {
            vertical-align: bottom;
        }

        .amount-col {
            width: 50%;
        }

        .amount-value {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .footer-label {
            display: inline-block;
            background-color: #1a1a1a;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 20px;
        }

        .firma-col {
            width: 50%;
            text-align: center;
        }

        .generated-note {
            margin-top: 30px;
            text-align: right;
            font-size: 8.5px;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logo_newharvest)
                        <img src="{{ $logo_newharvest }}" alt="New Harvest">
                    @endif
                </td>
                <td class="title-cell">
                    <h1>VOUCHER</h1>
                    <div class="voucher-number">N°: {{ $remito_code }}</div>
                    <div class="date-label">Fecha:</div>
                    <div class="date-boxes">
                        <span class="date-box">{{ \Carbon\Carbon::parse($fecha)->format('d') }}</span>
                        <span class="date-box">{{ \Carbon\Carbon::parse($fecha)->format('m') }}</span>
                        <span class="date-box">{{ \Carbon\Carbon::parse($fecha)->format('Y') }}</span>
                    </div>
                </td>
                <td class="company-cell">
                    @if($logo_empresa)
                        <img src="{{ $logo_empresa }}" alt="{{ $empresa }}">
                    @endif
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- Cuerpo -->
        <table class="body-table">
            <tr>
                <td class="data-col">
                    <div class="data-row"><div class="data-row-inner">
                        <div class="data-label">Empresa:</div>
                        <div class="data-value">{{ $empresa }}</div>
                    </div></div>

                    <div class="data-row"><div class="data-row-inner">
                        <div class="data-label">Origen:</div>
                        <div class="data-value">{{ $origen }}{{ $hora_origen && $hora_origen !== '--:--' ? ' (' . $hora_origen . 'hs)' : '' }}</div>
                    </div></div>

                    <div class="data-row"><div class="data-row-inner">
                        <div class="data-label">Destino:</div>
                        <div class="data-value">{{ $destino }}{{ $hora_destino && $hora_destino !== '--:--' ? ' (' . $hora_destino . 'hs)' : '' }}</div>
                    </div></div>

                    <div class="data-row"><div class="data-row-inner">
                        <div class="data-label">Tiempo espera:</div>
                        <div class="data-value">{{ $tiempo_espera }} min</div>
                    </div></div>
                </td>
                <td class="signature-col">
                    <div class="signature-passenger-label">Firma del pasajero</div>
                    @if($firma_url)
                        <img src="{{ $firma_url }}" class="signature-passenger-img" alt="Firma">
                    @else
                        <div class="signature-placeholder"></div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Footer: monto + firma -->
        <table class="footer-table">
            <tr>
                <td class="amount-col">
                    <div class="amount-value">$ {{ $monto }}</div>
                    <div class="footer-label">Monto:</div>
                </td>
                <td class="firma-col">
                    <div class="footer-label">Firma:</div>
                </td>
            </tr>
        </table>

        <div class="generated-note">Generado el {{ $generado_en }}</div>
    </div>
</body>
</html>
