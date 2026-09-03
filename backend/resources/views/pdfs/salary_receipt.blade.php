<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de sueldo #{{ $numero_recibo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 24px; }

        table.frame { width: 100%; border-collapse: collapse; }
        table.frame td { border: 1px solid #333; padding: 4px 6px; vertical-align: top; }

        .header-row td { padding: 6px; }
        .empresa-nombre { font-weight: bold; font-size: 12px; }
        .empresa-datos { font-size: 9px; color: #333; }
        .recibo-numero { text-align: right; font-weight: bold; font-size: 11px; }

        .label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #444; }
        .value { font-size: 10px; font-weight: bold; }

        table.conceptos { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.conceptos th, table.conceptos td {
            border: 1px solid #333;
            padding: 4px 6px;
            font-size: 9px;
        }
        table.conceptos th {
            background-color: #eee;
            text-align: left;
            text-transform: uppercase;
            font-size: 8px;
        }
        table.conceptos td.num { text-align: right; }
        table.conceptos tbody tr td { min-height: 14px; }

        table.totales { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.totales td {
            border: 1px solid #333;
            padding: 6px;
            font-size: 9px;
        }
        table.totales td.total-label { font-weight: bold; text-transform: uppercase; }
        table.totales td.total-value { text-align: right; font-weight: bold; }
        .neto-row td { background-color: #f2f2f2; font-size: 11px; }

        .firmas { width: 100%; margin-top: 30px; }
        .firma-box { width: 48%; display: inline-block; vertical-align: top; font-size: 9px; }
        .firma-linea { border-top: 1px solid #333; margin-top: 30px; padding-top: 4px; }
        .firma-sello { font-size: 8px; color: #333; line-height: 1.3; }
        .firma-pendiente { color: #999; font-style: italic; }
    </style>
</head>
<body>
<div class="page">

    <table class="frame header-row">
        <tr>
            <td style="width: 60%;">
                <div class="empresa-nombre">{{ $empresa_nombre }}</div>
                <div class="empresa-datos">{{ $empresa_domicilio }}</div>
                @if($empresa_cuit)
                    <div class="empresa-datos">C.U.I.T. Nº: {{ $empresa_cuit }}</div>
                @endif
            </td>
            <td style="width: 40%;" class="recibo-numero">
                Nº RECIBO: {{ $numero_recibo }}
            </td>
        </tr>
    </table>

    <table class="frame" style="margin-top: -1px;">
        <tr>
            <td style="width: 50%;">
                <div class="label">Apellido y Nombre</div>
                <div class="value">{{ $apellido_nombre }}</div>
            </td>
            <td style="width: 25%;">
                <div class="label">Nº Legajo</div>
                <div class="value">{{ $legajo }}</div>
            </td>
            <td style="width: 25%;">
                <div class="label">C.U.I.L. Nº</div>
                <div class="value">{{ $cuil }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Cargo</div>
                <div class="value">{{ $cargo }}</div>
            </td>
            <td>
                <div class="label">Fecha ingreso</div>
                <div class="value">{{ $fecha_ingreso }}</div>
            </td>
            <td>
                <div class="label">Período</div>
                <div class="value">{{ $periodo }}</div>
            </td>
        </tr>
    </table>

    <table class="conceptos">
        <thead>
        <tr>
            <th style="width: 6%;">Cod</th>
            <th style="width: 34%;">Concepto</th>
            <th style="width: 12%;">Cantidad</th>
            <th style="width: 16%;">Rem. C/D</th>
            <th style="width: 16%;">No Rem.</th>
            <th style="width: 16%;">Deducciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($conceptos as $c)
            <tr>
                <td>{{ $c['code'] }}</td>
                <td>{{ $c['description'] }}</td>
                <td class="num">{{ $c['quantity'] !== null ? number_format($c['quantity'], 2, ',', '.') : '' }}</td>
                <td class="num">{{ $c['remunerative_amount'] > 0 ? number_format($c['remunerative_amount'], 2, ',', '.') : '' }}</td>
                <td class="num">{{ $c['non_remunerative_amount'] > 0 ? number_format($c['non_remunerative_amount'], 2, ',', '.') : '' }}</td>
                <td class="num">{{ $c['deduction_amount'] > 0 ? number_format($c['deduction_amount'], 2, ',', '.') : '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center; color:#999;">Sin conceptos cargados</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <table class="totales">
        <tr>
            <td class="total-label" style="width: 25%;">Total Remunerativo</td>
            <td class="total-value" style="width: 25%;">$ {{ number_format($total_remunerativo, 2, ',', '.') }}</td>
            <td class="total-label" style="width: 25%;">Total No Remunerativo</td>
            <td class="total-value" style="width: 25%;">$ {{ number_format($total_no_remunerativo, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="total-label">Total Deducciones</td>
            <td class="total-value">$ {{ number_format($total_deducciones, 2, ',', '.') }}</td>
            <td class="total-label neto-row">Importe Neto</td>
            <td class="total-value neto-row">$ {{ number_format($importe_neto, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="firmas">
        <div class="firma-box">
            <div class="firma-linea">
                FIRMA EMPLEADO<br>
                @if($empleado_firmado)
                    <span class="firma-sello">
                        Documento firmado digitalmente por<br>
                        {{ $empleado_nombre }}<br>
                        DNI: {{ $empleado_cuil }} - CONFORME<br>
                        {{ $empleado_firmado_fecha }}
                    </span>
                @else
                    <span class="firma-pendiente">Pendiente de confirmación del empleado</span>
                @endif
            </div>
        </div>
        <div class="firma-box" style="float: right; text-align: right;">
            <div class="firma-linea">
                FIRMA EMPLEADOR<br>
                @if($empresa_firmado)
                    <span class="firma-sello">
                        Firmado por la empresa<br>
                        {{ $empresa_firmado_fecha }}
                    </span>
                @else
                    <span class="firma-pendiente">Pendiente de firma de la empresa</span>
                @endif
            </div>
        </div>
    </div>

</div>
</body>
</html>
