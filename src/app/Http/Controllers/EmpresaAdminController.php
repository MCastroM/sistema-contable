<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmpresaRequest;
use App\Models\Empresa;
use App\Services\PlanCuentasService;
use Illuminate\Http\Request;

class EmpresaAdminController extends Controller
{
    /** Listado de todas las empresas (activas e inactivas). */
    public function index()
    {
        $empresas = Empresa::withCount(['cuentas', 'periodos', 'comprobantes'])
            ->orderBy('razon_social')
            ->get();

        return view('empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('empresas.create');
    }

    /**
     * Crea la empresa y, si se marcó la casilla, instala de inmediato
     * el plan de cuentas estándar — mismo servicio que usábamos en Tinker.
     */
    public function store(EmpresaRequest $request, PlanCuentasService $planService)
    {
        $empresa = Empresa::create($request->validated() + ['activa' => true]);

        $mensaje = "Empresa {$empresa->razon_social} creada.";

        if ($request->boolean('crear_periodo_actual')) {
            $empresa->periodos()->firstOrCreate(['anio' => now()->year]);
            $mensaje .= ' Período ' . now()->year . ' abierto.';
        }

        if ($request->boolean('instalar_plan')) {
            try {
                $total = $planService->instalarEn($empresa);
                $mensaje .= " Plan de cuentas instalado ({$total} cuentas).";
            } catch (\Throwable $e) {
                $mensaje .= ' (No se pudo instalar el plan: ' . $e->getMessage() . ')';
            }
        }

        return redirect()->route('empresas.show', $empresa)->with('status', $mensaje);
    }

    /** Detalle de la empresa: datos + sus períodos. */
    public function show(Empresa $empresa)
    {
        $empresa->load(['periodos' => fn ($q) => $q->orderByDesc('anio')]);

        $resumen = [
            'cuentas'      => $empresa->cuentas()->count(),
            'comprobantes' => $empresa->comprobantes()->count(),
        ];

        return view('empresas.show', compact('empresa', 'resumen'));
    }

    public function edit(Empresa $empresa)
    {
        return view('empresas.edit', compact('empresa'));
    }

    public function update(EmpresaRequest $request, Empresa $empresa)
    {
        $empresa->update($request->validated());

        return redirect()->route('empresas.show', $empresa)
            ->with('status', 'Datos de la empresa actualizados.');
    }

    /**
     * Activar/desactivar: NUNCA se borra una empresa (integridad de
     * datos históricos). Una empresa inactiva no aparece como opción
     * en el selector del dashboard.
     */
    public function toggleActiva(Empresa $empresa)
    {
        $empresa->update(['activa' => ! $empresa->activa]);

        $accion = $empresa->activa ? 'activada' : 'desactivada';

        return back()->with('status', "Empresa {$accion}.");
    }

    /** Instalar el plan de cuentas manualmente (si no se hizo al crear). */
    public function instalarPlan(Empresa $empresa, PlanCuentasService $planService)
    {
        try {
            $total = $planService->instalarEn($empresa);
            return back()->with('status', "Plan de cuentas instalado: {$total} cuentas.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }
}
