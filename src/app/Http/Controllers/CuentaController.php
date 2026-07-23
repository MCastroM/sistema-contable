<?php

namespace App\Http\Controllers;

use App\Models\Empresa;

class CuentaController extends Controller
{
    /**
     * Árbol del plan de cuentas de la empresa activa.
     */
    public function index()
    {
        $empresa = Empresa::find(session('empresa_activa_id'))
            ?? Empresa::where('activa', true)->orderBy('razon_social')->first();

        if (! $empresa) {
            return redirect()->route('dashboard');
        }

        // Una sola consulta trae TODO el plan; el árbol se arma en memoria.
        // (121 filas: liviano. Con eager loading recursivo serían N consultas.)
        $cuentas = $empresa->cuentas()->orderBy('codigo')->get();

        // Agrupar por padre: $porPadre[null] = raíces, $porPadre[id] = hijas de id
        $porPadre = $cuentas->groupBy('padre_id');

        $totales = [
            'total'      => $cuentas->count(),
            'imputables' => $cuentas->where('imputable', true)->count(),
        ];

        return view('cuentas.index', [
            'empresa'  => $empresa,
            'raices'   => $porPadre->get('') ?? $porPadre->get(null) ?? collect(),
            'porPadre' => $porPadre,
            'totales'  => $totales,
        ]);
    }
}
