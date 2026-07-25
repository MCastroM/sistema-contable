<?php

namespace App\Http\Controllers;

use App\Models\BoletaHonorario;
use App\Models\Empresa;
use App\Services\CentralizacionHonorariosService;
use Illuminate\Http\Request;

class BoletaHonorarioController extends Controller
{
    public function index(Request $request, Empresa $empresa)
    {
        $periodo = $empresa->periodo($request->integer('anio', now()->year));
        $mes = $request->integer('mes', now()->month);

        $boletas = collect();
        if ($periodo) {
            $boletas = BoletaHonorario::where('empresa_id', $empresa->id)
                ->where('periodo_id', $periodo->id)
                ->where('mes', $mes)
                ->with('cuentaGasto:id,codigo,nombre')
                ->orderBy('nro')
                ->get();
        }

        return view('honorarios.index', [
            'empresa' => $empresa, 'periodo' => $periodo, 'mes' => $mes,
            'boletas' => $boletas, 'anio' => $request->integer('anio', now()->year),
        ]);
    }

    public function store(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'anio'             => ['required', 'integer'],
            'mes'              => ['required', 'integer', 'min:1', 'max:12'],
            'nro'              => ['required', 'integer', 'min:1'],
            'boleta'           => ['required', 'string', 'max:30'],
            'rut_prestador'    => ['required', 'string', 'max:12'],
            'nombre_prestador' => ['required', 'string', 'max:200'],
            'fecha'            => ['required', 'date'],
            'brutos'           => ['required', 'numeric', 'min:0'],
            'retencion'        => ['nullable', 'numeric', 'min:0'],
            'total'            => ['required', 'numeric', 'min:0'],
            'cuenta_gasto_id'  => ['nullable', 'exists:cuentas,id'],
        ]);

        $periodo = $empresa->periodo($validated['anio'])
            ?? throw \Illuminate\Validation\ValidationException::withMessages([
                'anio' => "No existe el período {$validated['anio']} para esta empresa.",
            ]);

        BoletaHonorario::create(array_merge($validated, [
            'empresa_id' => $empresa->id,
            'periodo_id' => $periodo->id,
        ]));

        return back()->with('status', 'Boleta de honorarios agregada.');
    }

    public function destroy(Empresa $empresa, BoletaHonorario $boleta)
    {
        abort_if($boleta->empresa_id !== $empresa->id, 403);
        abort_if($boleta->estaCentralizado(), 422, 'No se puede eliminar: ya fue centralizada.');

        $boleta->delete();

        return back()->with('status', 'Boleta eliminada.');
    }

    public function asignarCuenta(Request $request, Empresa $empresa, BoletaHonorario $boleta)
    {
        abort_if($boleta->empresa_id !== $empresa->id, 403);
        abort_if($boleta->estaCentralizado(), 422, 'No se puede editar: ya fue centralizada.');

        $validated = $request->validate(['cuenta_gasto_id' => ['required', 'exists:cuentas,id']]);
        $boleta->update($validated);

        return back()->with('status', 'Cuenta asignada.');
    }

    public function centralizar(Request $request, Empresa $empresa, CentralizacionHonorariosService $servicio)
    {
        $validated = $request->validate([
            'anio' => ['required', 'integer'],
            'mes'  => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $periodo = $empresa->periodo($validated['anio']);
        abort_if(! $periodo, 404, 'Período no encontrado.');

        try {
            $comprobante = $servicio->centralizar($empresa, $periodo, $validated['mes']);
            return back()->with('status', "Centralizado: comprobante {$comprobante->folio()}.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }
}
