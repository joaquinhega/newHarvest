<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sanción {{ $sanction_number }}</title>
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
        .title-meta { font-size: 12px; margin-top: 10px; }

        .divider { border-bottom: 2px solid #1a1a1a; margin: 24px 0 30px 0; }

        .data-row { display: table; width: 100%; margin-bottom: 14px; }
        .data-row-inner { display: table-row; }
        .data-label {
            display: table-cell; background-color: #1a1a1a; color: #fff;
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; padding: 10px 14px; width: 150px; white-space: nowrap;
        }
        .data-value { display: table-cell; font-size: 13px; font-weight: 600; padding: 10px 0 10px 16px; vertical-align: middle; }

        .measure-box {
            background-color: #f5f5f5; border-left: 4px solid #1a1a1a;
            padding: 16px 18px; margin: 20px 0; font-size: 15px; font-weight: bold;
        }
        .measure-box .measure-days { font-weight: normal; font-size: 12px; color: #555; margin-top: 4px; }

        .section-title {
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; color: #555; margin: 24px 0 8px 0;
        }
        .reason-box {
            border: 1px solid #ccc; border-radius: 4px; padding: 14px 16px;
            font-size: 12.5px; line-height: 1.6; min-height: 70px;
        }

        .signature-area { margin-top: 60px; text-align: center; }
        .signature-line { border-bottom: 1px solid #999; width: 260px; height: 60px; margin: 0 auto 8px auto; }
        .signature-img { max-width: 220px; max-height: 70px; margin-bottom: 8px; }
        .signature-caption { font-size: 11px; color: #555; }
        .signature-confirmed { font-size: 11px; color: #1a1a1a; font-weight: bold; margin-top: 4px; }

        .generated-note { margin-top: 50px; text-align: right; font-size: 8.5px; color: #aaa; }
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
                    <h1>ACTA DE SANCIÓN DISCIPLINARIA</h1>
                    <div class="title-sub">Documento interno de Recursos Humanos</div>
                    <div class="title-meta">N° de acta: {{ $sanction_number }} &nbsp;·&nbsp; Fecha: {{ $fecha }}</div>
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

        <div class="measure-box">
            Medida aplicada: {{ strtoupper($tipo) }}
            @if($tipo === 'Suspensión' && $dias > 0)
                <div class="measure-days">{{ $dias }} {{ $dias === 1 ? 'día' : 'días' }} de suspensión</div>
            @endif
        </div>

        <div class="section-title">Motivo</div>
        <div class="reason-box">{{ $motivo }}</div>

        <div class="signature-area">
            @if($firma_url)
                <img src="{{ $firma_url }}" class="signature-img" alt="">
            @else
                <div class="signature-line"></div>
            @endif
            <div class="signature-caption">Firma de conformidad del empleado</div>
            @if($firmado)
                <div class="signature-confirmed">✓ Confirmado{{ $firmado_en ? ' — ' . $firmado_en : '' }}</div>
            @endif
        </div>

        <div class="generated-note">Generado el {{ $generado_en }}</div>
    </div>
</body>
</html>
