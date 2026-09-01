<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pendiente');
        $search = $request->input('search', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = $this->buildFilteredQuery($status, $search, $dateFrom, $dateTo);

        $vouchers = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($v) {
                $signatureUrl = $v->signature_path;
                if ($signatureUrl && str_starts_with($signatureUrl, '../')) {
                    $signatureUrl = substr($signatureUrl, 2);
                }

                return [
                    'id' => $v->id,
                    'remito_code' => $v->remito_code,
                    'fecha' => $v->date ? $v->date->format('Y-m-d') : '',
                    'fecha_formateada' => $v->date ? $v->date->format('d-m-Y') : '',
                    'chofer' => $v->user ? "{$v->user->first_name} {$v->user->last_name}" : 'Sin Chofer',
                    'chofer_user_id' => $v->user_id,
                    'pasajero' => $v->passenger_name ?: 'Sin Pasajero',
                    'empresa' => $v->company ? $v->company->name : ($v->company_name ?: 'Particular'),
                    'company_id' => $v->company_id,
                    'origen' => $v->origin ?: '',
                    'destino' => $v->destination ?: '',
                    'hora_origen' => $v->pickup_time ?: '--:--',
                    'hora_destino' => $v->dropoff_time ?: '--:--',
                    'tiempo_espera' => (int) ($v->wait_time ?: 0),
                    'monto' => (float) ($v->amount ?: 0),
                    'observaciones' => $v->observation ?: '',
                    'firma' => $signatureUrl,
                    'status' => $v->status === 'aprobado' ? 'aprobado' : 'pendiente',
                ];
            });

        $companies = Company::where('borrado', false)
            ->orderBy('name')
            ->get(['id', 'name']);

        $choferes = User::whereHas('role', function ($q) {
                $q->where('name', 'chofer');
            })
            ->where('active', true)
            ->orderBy('first_name')
            ->get(['id_usuario', 'first_name', 'last_name']);

        return Inertia::render('Operaciones/Vouchers', [
            'vouchers' => $vouchers,
            'companies' => $companies,
            'choferes' => $choferes,
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
        $vouchers = $query->orderByDesc('date')->orderByDesc('id')->get();

        $filename = "vouchers_{$status}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($vouchers) {
            $handle = fopen('php://output', 'w');
            
            // BOM UTF-8 para visualización de caracteres en Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID Remito',
                'Fecha',
                'Chofer',
                'Pasajero',
                'Empresa',
                'Origen',
                'Hora Salida',
                'Destino',
                'Hora Llegada',
                'Espera (Min)',
                'Monto ($)',
                'Estado',
                'Observaciones'
            ], ';');

            foreach ($vouchers as $v) {
                fputcsv($handle, [
                    $v->remito_code,
                    $v->date ? $v->date->format('d/m/Y') : '',
                    $v->user ? "{$v->user->first_name} {$v->user->last_name}" : 'Sin Chofer',
                    $v->passenger_name ?: 'Sin Pasajero',
                    $v->company ? $v->company->name : ($v->company_name ?: 'Particular'),
                    $v->origin,
                    $v->pickup_time,
                    $v->destination,
                    $v->dropoff_time,
                    $v->wait_time ?: 0,
                    number_format((float)$v->amount, 2, ',', '.'),
                    $v->status === 'aprobado' ? 'Aprobado' : 'Pendiente',
                    $v->observation,
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
        $query = Voucher::query()->with(['company', 'user'])->where('borrado', false);

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
                $q->where('passenger_name', 'like', "%{$search}%")
                  ->orWhere('origin', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('remito_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    public function approve(Request $request, $id)
    {
        $voucher = Voucher::where('borrado', false)->findOrFail($id);
        $voucher->status = 'aprobado';

        if ($request->filled('amount')) {
            $voucher->amount = $request->input('amount');
        }

        $voucher->save();

        return redirect()->back()->with('message', "Voucher #{$voucher->remito_code} aprobado.");
    }

    public function disapprove(Request $request, $id)
    {
        $voucher = Voucher::where('borrado', false)->findOrFail($id);
        $voucher->status = 'pendiente';
        $voucher->save();

        return redirect()->back()->with('message', "Voucher #{$voucher->remito_code} vuelto a estado pendiente.");
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::where('borrado', false)->findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'user_id' => 'nullable|integer|exists:users,id_usuario',
            'passenger_name' => 'nullable|string|max:150',
            'origin' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'pickup_time' => 'nullable|string|max:8',
            'dropoff_time' => 'nullable|string|max:8',
            'wait_time' => 'nullable|string|max:20',
            'date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'observation' => 'nullable|string|max:600',
        ]);

        if (array_key_exists('company_id', $validated)) {
            $voucher->company_id = $validated['company_id'];
            if ($validated['company_id']) {
                $voucher->company_name = Company::where('id', $validated['company_id'])->value('name');
            }
        }

        if (array_key_exists('user_id', $validated) && $validated['user_id']) {
            $voucher->user_id = $validated['user_id'];
        }

        if (array_key_exists('passenger_name', $validated)) {
            $voucher->passenger_name = $validated['passenger_name'];
        }

        if (array_key_exists('origin', $validated)) {
            $voucher->origin = $validated['origin'];
        }

        if (array_key_exists('destination', $validated)) {
            $voucher->destination = $validated['destination'];
        }

        if (array_key_exists('pickup_time', $validated)) {
            $voucher->pickup_time = $validated['pickup_time'];
        }

        if (array_key_exists('dropoff_time', $validated)) {
            $voucher->dropoff_time = $validated['dropoff_time'];
        }

        if (array_key_exists('wait_time', $validated)) {
            $voucher->wait_time = $validated['wait_time'];
        }

        if (array_key_exists('date', $validated) && $validated['date']) {
            $voucher->date = $validated['date'];
        }

        if (array_key_exists('amount', $validated)) {
            $voucher->amount = $validated['amount'];
        }

        if (array_key_exists('observation', $validated)) {
            $voucher->observation = $validated['observation'];
        }

        $voucher->save();

        return redirect()->back()->with('message', "Voucher #{$voucher->remito_code} actualizado.");
    }
}