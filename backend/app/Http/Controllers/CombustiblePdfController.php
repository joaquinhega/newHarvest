<?php

namespace App\Http\Controllers;

use App\Models\Combustible;
use Barryvdh\DomPDF\Facade\Pdf;

class CombustiblePdfController extends Controller
{
    /**
     * Generar y descargar PDF de un remito de combustible
     */
    public function generate($id)
    {
        $combustible = Combustible::where('borrado', false)->findOrFail($id);

        // Preparar datos del remito
        $data = [
            'id' => $combustible->id,
            'remito_code' => $combustible->remito_code ?? "COMB-{$combustible->id}",
            'fecha' => $combustible->date ? $combustible->date->format('d/m/Y') : '',
            'chofer' => $combustible->user ? "{$combustible->user->first_name} {$combustible->user->last_name}" : 'Sin Chofer',
            'empresa' => $combustible->company ? $combustible->company->name : ($combustible->company_name ?: 'Particular'),
            'monto' => number_format((float)($combustible->amount ?: 0), 2, ',', '.'),
            'litros' => $combustible->liters ?: '0',
            'observaciones' => $combustible->observation ?: '',
            'status' => $combustible->status === 'aprobado' ? 'Aprobado' : 'Pendiente',
            'logo_empresa' => $combustible->company && $combustible->company->logo_blob
                ? 'data:' . ($combustible->company->logo_mime ?: 'image/png') . ';base64,' . base64_encode($combustible->company->logo_blob)
                : null,
        ];

        // Generar PDF
        $pdf = Pdf::loadView('pdfs.combustible', $data);

        return $pdf->download("Combustible_{$data['remito_code']}.pdf");
    }
}
