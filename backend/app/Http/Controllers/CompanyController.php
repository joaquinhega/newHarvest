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
                ];
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
        ], [
            'name.required' => 'El nombre de la empresa es obligatorio.',
        ]);

        Company::create([
            'name' => $validated['name'],
            'borrado' => false,
        ]);

        return redirect()->route('empresas.index')->with('message', 'Empresa creada exitosamente.');
    }

    /**
     * Actualiza la razón social de una empresa existente.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ], [
            'name.required' => 'El nombre de la empresa es obligatorio.',
        ]);

        $company = Company::findOrFail($id);
        $company->update([
            'name' => $validated['name'],
        ]);

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