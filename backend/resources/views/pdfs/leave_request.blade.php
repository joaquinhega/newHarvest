<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Licencia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1C1B22; }

        .header-band { background-color: #3B0764; padding: 35px 55px 28px 55px; color: #fff; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo-cell img { max-height: 60px; max-width: 180px; width: auto; height: auto; display: block; }
        .title-cell { text-align: right; }
        .title-cell h1 { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; }
        .title-sub { font-size: 11px; opacity: 0.75; margin-top: 4px; }

        .body-content { padding: 35px 55px; }

        .data-row { display: table; width: 100%; margin-bottom: 12px; }
        .data-row-inner { display: table-row; }
        .data-label {
            display: table-cell; background-color: #F3E8FD; color: #5B1F63;
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; padding: 10px 14px; width: 150px; white-space: nowrap;
            border-radius: 4px 0 0 4px;
        }
        .data-value { display: table-cell; font-size: 13px; font-weight: 600; padding: 10px 0 10px 16px; vertical-align: middle; }

        .period-box { background-color: #8A2E93; color: #fff; padding: 16px 18px; margin: 22px 0; font-size: 14px; font-weight: bold; border-radius: 6px; }
        .period-box .period-days { font-weight: normal; font-size: 12px; opacity: 0.85; margin-top: 4px; }

        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #78757F; margin: 24px 0 8px 0; }
        .diagnosis-box { border: 1px solid #D2CFD6; border-radius: 6px; padding: 14px 16px; font-size: 12.5px; line-height: 1.6; }

        .status-badge {
            display: inline-block; padding: 10px 20px; font-size: 13px; font-weight: bold;
            text-transform: uppercase; letter-spacing: 0.5px; margin-top: 24px; border-radius: 6px;
        }
        .status-aprobada { background-color: #dcefe6; color: #0B6E4F; }
        .status-rechazada { background-color: #fbddbb; color: #B3261E; }
        .status-pendiente { background-color: #F5E9D3; color: #9A5B0A; }

        .review-note { font-size: 11px; color: #78757F; margin-top: 10px; }

        .generated-note { margin-top: 60px; text-align: right; font-size: 8.5px; color: #aaa; padding: 0 55px 20px 55px; }
    </style>
</head>
<body>
    <div class="header-band">
        <table class="header-table">
            <tr>
                <td class="logo-cell" style="width:35%">
                    @if($logo_newharvest)
                        <img src="{{ $logo_newharvest }}" alt="New Harvest">
                    @endif
                </td>
                <td class="title-cell">
                    <h1>CONSTANCIA DE LICENCIA</h1>
                    <div class="title-sub">{{ $tipo }} · Recursos Humanos</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="body-content">
        <div class="data-row"><div class="data-row-inner">
            <div class="data-label">Empleado:</div>
            <div class="data-value">{{ $nombre_completo }}</div>
        </div></div>
        <div class="data-row"><div class="data-row-inner">
            <div class="data-label">Legajo:</div>
            <div class="data-value">#{{ str_pad($legajo, 3, '0', STR_PAD_LEFT) }}</div>
        </div></div>
        <div class="data-row"><div class="data-row-inner">
            <div class="data-label">CUIL:</div>
            <div class="data-value">{{ $cuil }}</div>
        </div></div>
        <div class="data-row"><div class="data-row-inner">
            <div class="data-label">Puesto:</div>
            <div class="data-value">{{ $puesto }}</div>
        </div></div>

        <div class="period-box">
            {{ $tipo }}: {{ $desde }} al {{ $hasta }}
            <div class="period-days">{{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}</div>
        </div>

        @if($diagnostico)
            <div class="section-title">Diagnóstico</div>
            <div class="diagnosis-box">{{ $diagnostico }}</div>
        @endif

        <div class="status-badge status-{{ $estado_raw }}">{{ $estado }}</div>
        @if($revisor && $estado_raw !== 'pendiente')
            <div class="review-note">
                {{ $estado }} por {{ $revisor }}{{ $accion_en ? ' — ' . $accion_en : '' }}
            </div>
        @endif
    </div>

    <div class="generated-note">Generado el {{ $generado_en }}</div>
</body>
</html>
