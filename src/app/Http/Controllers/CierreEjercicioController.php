<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\CierreEjercicioService;
use Illuminate\Http\Request;

class CierreEjercicioController extends Controller
{
    public function cerrar(Request $request, Empresa $empresa, CierreEjercicioService $servicio)
    {
        $validated = $request->validate(['anio' => ['required', 'integer', 'min:2000', 'max:2100']]);

        try {
            $comprobante = $servicio->cerrar($empresa, $validated['anio']);
            return back()->with('status', "Ejercicio {$validated['anio']} cerrado: comprobante {$comprobante->folio()}.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }
}
