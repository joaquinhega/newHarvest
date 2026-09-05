<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Licencia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1a1a1a; }
        .page { padding: 45px 55px; }

        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo-cell { width: 30%; }
        .logo-cell img { max-height: 75px; max-width: 200px; }
        .title-cell { width: 70%; text-align: right; }
        .title-cell h1 { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; }
        .title-sub { font-size: 12px; color: #555; margin-top: 4px; }

        .divider { border-bottom: 2px solid #1a1a1a; margin: 24px 0 30px 0; }

        .data-row { display: table; width: 100%; margin-bottom: 14px; }
        .data-row-inner { display: table-row; }
        .data-label {
            display: table-cell; background-color: #1a1a1a; color: #fff;
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; padding: 10px 14px; width: 150px; white-space: nowrap;
        }
        .data-value { display: table-cell; font-size: 13px; font-weight: 600; padding: 10px 0 10px 16px; vertical-align: middle; }

        .period-box {
            background-color: #f5f5f5; border-left: 4px solid #1a1a1a;
            padding: 16px 18px; margin: 20px 0; font-size: 14px; font-weight: bold;
        }
        .period-box .period-days { font-weight: normal; font-size: 12px; color: #555; margin-top: 4px; }

        .section-title {
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555; margin: 24px 0 8px 0;
        }
        .diagnosis-box {
            border: 1px solid #ccc; border-radius: 4px; padding: 14px 16px;
            font-size: 12.5px; line-height: 1.6;
        }

        .status-badge {
            display: inline-block; padding: 10px 20px; font-size: 13px; font-weight: bold;
            text-transform: uppercase; letter-spacing: 0.5px; margin-top: 24px;
        }
        .status-aprobada { background-color: #dcefe6; color: #0B6E4F; }
        .status-rechazada { background-color: #fbddbb; color: #B3261E; }
        .status-pendiente { background-color: #f5e9d3; color: #9A5B0A; }

        .review-note { font-size: 11px; color: #555; margin-top: 10px; }

        .generated-note { margin-top: 60px; text-align: right; font-size: 8.5px; color: #aaa; }
    </style>
</head>
<body>
    <div class="page">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
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

        <div class="divider"></div>

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

        <div class="generated-note">Generado el {{ $generado_en }}</div>
    </div>
</body>
</html>
