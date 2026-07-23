<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Empresa;
use App\Models\Indicador;

class DashboardController extends Controller
{
    public function index()
    {
        // Todas las empresas activas (para el selector)
        $empresas = Empresa::where('activa', true)->orderBy('razon_social')->get();

        // Empresa activa: la elegida en sesión, o la primera disponible
        $empresa = Empresa::find(session('empresa_activa_id')) ?? $empresas->first();

        // Indicadores: último valor disponible de cada uno
        $indicadores = collect(['uf', 'utm', 'dolar', 'ipc', 'tpm'])
            ->mapWithKeys(fn ($codigo) => [$codigo => Indicador::ultimo($codigo)]);

        // Resumen contable de la empresa activa
        $resumen = null;
        if ($empresa) {
            $periodo = $empresa->periodo(now()->year);

            $resumen = [
                'periodo'       => $periodo,
                'cuentas'       => $empresa->cuentas()->count(),
                'imputables'    => $empresa->cuentas()->where('imputable', true)->count(),
                'borradores'    => $empresa->comprobantes()->where('estado', Comprobante::BORRADOR)->count(),
                'aprobados'     => $empresa->comprobantes()->where('estado', Comprobante::APROBADO)->count(),
            ];
        }

        return view('dashboard', compact('empresas', 'empresa', 'indicadores', 'resumen'));
    }
}
