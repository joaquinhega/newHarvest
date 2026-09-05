<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class LeaveRequestPdfController extends Controller
{
    private const TIPO_LABELS = [
        'vacaciones' => 'Vacaciones',
        'certificado_medico' => 'Certificado médico',
        'licencia_especial' => 'Licencia especial',
    ];

    private const ESTADO_LABELS = [
        'pendiente' => 'Pendiente',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
    ];

    public function generate($id)
    {
        $leave = LeaveRequest::with(['employee', 'reviewer'])->where('borrado', false)->findOrFail($id);
        $employee = $leave->employee;

        $data = [
            'id' => $leave->id,
            'tipo' => self::TIPO_LABELS[$leave->type] ?? $leave->type,
            'nombre_completo' => $employee ? "{$employee->last_name}, {$employee->first_name}" : 'Sin datos',
            'legajo' => $employee?->id,
            'cuil' => $employee?->cuil ?: '—',
            'puesto' => $employee?->position ?: '—',
            'desde' => $leave->start_date ? $leave->start_date->format('d/m/Y') : '',
            'hasta' => $leave->end_date ? $leave->end_date->format('d/m/Y') : '',
            'dias' => $leave->days_count,
            'diagnostico' => $leave->diagnosis,
            'estado' => self::ESTADO_LABELS[$leave->status] ?? $leave->status,
            'estado_raw' => $leave->status,
            'revisor' => $leave->reviewer ? "{$leave->reviewer->first_name} {$leave->reviewer->last_name}" : null,
            'accion_en' => $leave->action_at ? $leave->action_at->format('d/m/Y H:i') : null,
            'logo_newharvest' => $this->embedAsset('logo-newharvest-negro.png'),
            'generado_en' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdfs.leave_request', $data)->setPaper('a4', 'portrait');

        return $pdf->download("Licencia_{$data['id']}.pdf");
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
