<?php

namespace App\Http\Controllers;

use App\Models\Sanction;
use Barryvdh\DomPDF\Facade\Pdf;

class SanctionPdfController extends Controller
{
    public function generate($id)
    {
        $sanction = Sanction::with('employee')->where('borrado', false)->findOrFail($id);
        $employee = $sanction->employee;

        $data = [
            'sanction_number' => $sanction->sanction_number ?: $sanction->id,
            'fecha' => $sanction->date ? $sanction->date->format('d/m/Y') : '',
            'nombre_completo' => $employee ? "{$employee->last_name}, {$employee->first_name}" : 'Sin datos',
            'legajo' => $employee?->id,
            'cuil' => $employee?->cuil ?: '—',
            'puesto' => $employee?->position ?: '—',
            'tipo' => $sanction->type === 'suspension' ? 'Suspensión' : 'Apercibimiento',
            'dias' => $sanction->days_count,
            'motivo' => $sanction->reason,
            'firmado' => ! empty($sanction->signed_at) || $sanction->status === 'firmado',
            'firma_url' => $this->embedImage($sanction->signature_path),
            'firmado_en' => $sanction->signed_at ? $sanction->signed_at->format('d/m/Y H:i') : null,
            'logo_newharvest' => $this->embedAsset('logo-newharvest-negro.png'),
            'generado_en' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdfs.sanction', $data)->setPaper('a4', 'portrait');

        return $pdf->download("Sancion_{$data['sanction_number']}.pdf");
    }

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

    private function embedAsset(string $filename): ?string
    {
        $path = resource_path('assets/' . $filename);
        if (! file_exists($path)) {
            return null;
        }
        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }
}
