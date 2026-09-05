<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sanción {{ $sanction_number }}</title>
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
        .title-meta { font-size: 11px; margin-top: 10px; opacity: 0.9; }

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

        .measure-box { background-color: #8A2E93; color: #fff; padding: 16px 18px; margin: 22px 0; font-size: 15px; font-weight: bold; border-radius: 6px; }
        .measure-box .measure-days { font-weight: normal; font-size: 12px; opacity: 0.85; margin-top: 4px; }

        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #78757F; margin: 24px 0 8px 0; }
        .reason-box { border: 1px solid #D2CFD6; border-radius: 6px; padding: 14px 16px; font-size: 12.5px; line-height: 1.6; min-height: 70px; }

        .signature-area { margin-top: 55px; text-align: center; }
        .signature-line { border-bottom: 1px solid #999; width: 260px; height: 60px; margin: 0 auto 8px auto; }
        .signature-img { max-width: 220px; max-height: 70px; margin-bottom: 8px; }
        .signature-caption { font-size: 11px; color: #78757F; }
        .signature-confirmed { font-size: 11px; color: #0B6E4F; font-weight: bold; margin-top: 4px; }

        .generated-note { margin-top: 40px; text-align: right; font-size: 8.5px; color: #aaa; padding: 0 55px 20px 55px; }
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
                    <h1>ACTA DE SANCIÓN DISCIPLINARIA</h1>
                    <div class="title-sub">Documento interno de Recursos Humanos</div>
                    <div class="title-meta">N° de acta: {{ $sanction_number }} &nbsp;·&nbsp; Fecha: {{ $fecha }}</div>
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
    </div>

    <div class="generated-note">Generado el {{ $generado_en }}</div>
</body>
</html>
