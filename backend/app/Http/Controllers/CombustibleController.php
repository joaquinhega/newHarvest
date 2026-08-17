<?php

namespace App\Http\Controllers;

use App\Models\Combustible;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;

class CombustibleController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pendiente');
        $search = $request->input('search', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = $this->buildFilteredQuery($status, $search, $dateFrom, $dateTo);

        $remitos = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'remito_code' => $c->remito_code ?: 'S/N',
                    'patente' => $c->plate ?: 'S/P',
                    'chofer' => $c->user ? "{$c->user->first_name} {$c->user->last_name}" : ($c->driver_name ?: 'Chofer'),
                    'monto' => (float) ($c->amount ?: 0),
                    'fecha' => $c->date ? $c->date->format('Y-m-d') : '',
                    'fecha_formateada' => $c->date ? $c->date->format('d-m-Y') : '',
                    'status' => $c->status === 'aprobado' ? 'aprobado' : 'pendiente',
                ];
            });

        return Inertia::render('Operaciones/Combustible', [
            'remitos' => $remitos,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $status = $request->input('status', 'pendiente');
        $search = $request->input('search', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = $this->buildFilteredQuery($status, $search, $dateFrom, $dateTo);
        $remitos = $query->orderByDesc('date')->orderByDesc('id')->get();

        $filename = "combustible_{$status}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($remitos) {
            $handle = fopen('php://output', 'w');
            
            // BOM UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'N° Remito',
                'Patente',
                'Chofer',
                'Fecha',
                'Monto ($)',
                'Estado'
            ], ';');

            foreach ($remitos as $c) {
                fputcsv($handle, [
                    $c->remito_code ?: 'S/N',
                    $c->plate ?: 'S/P',
                    $c->user ? "{$c->user->first_name} {$c->user->last_name}" : ($c->driver_name ?: 'Chofer'),
                    $c->date ? $c->date->format('d/m/Y') : '',
                    number_format((float)$c->amount, 2, ',', '.'),
                    $c->status === 'aprobado' ? 'Aprobado' : 'Pendiente',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildFilteredQuery($status, $search, $dateFrom, $dateTo)
    {
        $query = Combustible::query()->with('user')->where('borrado', false);

        if ($status === 'aprobado') {
            $query->where('status', 'aprobado');
        } else {
            $query->where(function ($q) {
                $q->where('status', 'pendiente')->orWhereNull('status');
            });
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('plate', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('remito_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    public function approve(Request $request, $id)
    {
        $combustible = Combustible::where('borrado', false)->findOrFail($id);
        $combustible->status = 'aprobado';
        $combustible->save();

        return redirect()->back()->with('message', "Remito #{$combustible->remito_code} aprobado correctamente.");
    }
}