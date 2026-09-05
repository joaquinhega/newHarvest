<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class VoucherPdfController extends Controller
{
    /**
     * Generar y descargar PDF de un voucher
     */
    public function generate($id)
    {
        $voucher = Voucher::where('borrado', false)->findOrFail($id);

        $logoPath = resource_path('assets/logo-newharvest-negro.png');
        $logoNewHarvest = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        // Preparar datos del voucher
        $data = [
            'id' => $voucher->id,
            'remito_code' => $voucher->remito_code,
            'fecha' => $voucher->date ? $voucher->date->format('d/m/Y') : '',
            'fecha_dia' => $voucher->date ? $voucher->date->format('d') : '--',
            'fecha_mes' => $voucher->date ? $voucher->date->format('m') : '--',
            'fecha_anio' => $voucher->date ? $voucher->date->format('Y') : '----',
            'pasajero' => $voucher->passenger_name ?: 'Sin Pasajero',
            'empresa' => $voucher->company ? $voucher->company->name : ($voucher->company_name ?: 'Particular'),
            'chofer' => $voucher->user ? "{$voucher->user->first_name} {$voucher->user->last_name}" : 'Sin Chofer',
            'origen' => $voucher->origin ?: '',
            'destino' => $voucher->destination ?: '',
            'hora_origen' => $voucher->pickup_time ?: '--:--',
            'hora_destino' => $voucher->dropoff_time ?: '--:--',
            'tiempo_espera' => (int) ($voucher->wait_time ?: 0),
            'monto' => number_format((float)($voucher->amount ?: 0), 2, ',', '.'),
            'observaciones' => $voucher->observation ?: '',
            'firma' => $voucher->signature_path,
            'firma_url' => $this->embedImage($voucher->signature_path),
            'status' => $voucher->status === 'aprobado' ? 'Aprobado' : 'Pendiente',
            'logo_empresa' => $voucher->company && $voucher->company->logo_blob
                ? 'data:' . ($voucher->company->logo_mime ?: 'image/png') . ';base64,' . base64_encode($voucher->company->logo_blob)
                : null,
            'logo_newharvest' => $logoNewHarvest,
            'generado_en' => now()->format('d/m/Y H:i'),
        ];

        // Generar PDF en horizontal
        $pdf = Pdf::loadView('pdfs.voucher', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("Voucher_{$voucher->remito_code}.pdf");
    }

    /**
     * Convierte un archivo de storage/public en un data URI base64, para que
     * dompdf lo pueda renderizar siempre, sin depender de poder resolver
     * URLs externas (asset()) ni de tener habilitado el fetch remoto.
     */
    private function embedImage(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $absolutePath = storage_path('app/public/' . $relativePath);
        if (! file_exists($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absolutePath));
    }
}
