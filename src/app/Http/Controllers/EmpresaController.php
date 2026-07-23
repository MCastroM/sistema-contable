<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    /**
     * Cambia la empresa activa de la sesión.
     * La empresa activa filtra TODO lo que el usuario ve y hace.
     */
    public function seleccionar(Request $request)
    {
        $validated = $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
        ]);

        $empresa = Empresa::findOrFail($validated['empresa_id']);

        if (! $empresa->activa) {
            return back()->withErrors(['empresa_id' => 'La empresa está inactiva.']);
        }

        session(['empresa_activa_id' => $empresa->id]);

        return back()->with('status', "Trabajando en: {$empresa->razon_social}");
    }
}
