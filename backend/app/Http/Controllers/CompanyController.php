<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * Listado principal de empresas para el backoffice administrativo.
     */
    public function index()
    {
        $empresas = Company::query()
            ->where('borrado', false)
            ->withCount(['vouchers' => function ($query) {
                $query->where('borrado', false);
            }])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id ?? $company->id_empresa,
                    'nombre' => $company->name ?? $company->nombre,
                    'vouchers_count' => $company->vouchers_count ?? 0,
                    'estado' => 'Activa',
                    'logo_path' => $company->logo_path,
                    'logo_base64' => $company->logo_blob
                        ? 'data:' . ($company->logo_mime ?: 'image/png') . ';base64,' . base64_encode($company->logo_blob)
                        : null,
                ];
            });

        // Cargar vouchers reales para cada empresa
        $empresas = $empresas->map(function ($company) {
            $companyModel = Company::with('vouchers')->find($company['id']);
            $company['vouchers'] = $companyModel && $companyModel->vouchers
                ? $companyModel->vouchers->where('borrado', false)->map(function ($voucher) {
                    return [
                        'id' => $voucher->id,
                        'remito_code' => $voucher->remito_code,
                        'passenger_name' => $voucher->passenger_name ?: 'Sin Pasajero',
                        'origin' => $voucher->origin,
                        'destination' => $voucher->destination,
                        'date' => $voucher->date ? $voucher->date->format('d/m/Y') : '',
                        'amount' => $voucher->amount,
                        'status' => $voucher->status,
                    ];
                })->toArray()
                : [];
            return $company;
        });
        return Inertia::render('Empresas/Index', [
            'empresas' => $empresas,
        ]);
    }

    /**
     * Almacena una nueva empresa en el sistema.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'logo' => 'nullable|image|max:2048',
        ], [
            'name.required' => 'El nombre de la empresa es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen (JPG, PNG, etc.).',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ]);

        $company = Company::create([
            'name' => $validated['name'],
            'borrado' => false,
        ]);

        if ($request->hasFile('logo')) {
            $company->update([
                'logo_blob' => file_get_contents($request->file('logo')->getRealPath()),
                'logo_mime' => $request->file('logo')->getMimeType(),
            ]);
        }

        return redirect()->route('empresas.index')->with('message', 'Empresa creada exitosamente.');
    }

    /**
     * Actualiza la razón social de una empresa existente.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'logo' => 'nullable|image|max:2048',
        ], [
            'name.required' => 'El nombre de la empresa es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen (JPG, PNG, etc.).',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ]);

        $company = Company::findOrFail($id);
        $company->name = $validated['name'];

        if ($request->hasFile('logo')) {
            $company->logo_blob = file_get_contents($request->file('logo')->getRealPath());
            $company->logo_mime = $request->file('logo')->getMimeType();
        }

        $company->save();

        return redirect()->route('empresas.index')->with('message', 'Empresa actualizada correctamente.');
    }

    /**
     * Baja lógica de la empresa.
     */
    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->update([
            'borrado' => true,
        ]);

        return redirect()->route('empresas.index')->with('message', 'Empresa desactivada correctamente.');
    }
}