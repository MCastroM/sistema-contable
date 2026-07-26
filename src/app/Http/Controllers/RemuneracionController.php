<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\RemuneracionTrabajador;
use App\Services\CentralizacionRemuneracionesService;
use Illuminate\Http\Request;

class RemuneracionController extends Controller
{
    public function index(Request $request, Empresa $empresa)
    {
        $periodo = $empresa->periodo($request->integer('anio', now()->year));
        $mes = $request->integer('mes', now()->month);

        $filas = collect();
        if ($periodo) {
            $filas = RemuneracionTrabajador::where('empresa_id', $empresa->id)
                ->where('periodo_id', $periodo->id)
                ->where('mes', $mes)
                ->orderBy('nro')
                ->get();
        }

        $totales = [
            'total_haberes'  => $filas->reduce(fn ($a, $f) => bcadd($a, (string) $f->total_haberes, 2), '0'),
            'liquido'        => $filas->reduce(fn ($a, $f) => bcadd($a, (string) $f->liquido, 2), '0'),
        ];

        return view('remuneraciones.index', [
            'empresa' => $empresa, 'periodo' => $periodo, 'mes' => $mes,
            'filas' => $filas, 'totales' => $totales,
            'anio' => $request->integer('anio', now()->year),
        ]);
    }

    public function destroy(Empresa $empresa, RemuneracionTrabajador $trabajador)
    {
        abort_if($trabajador->empresa_id !== $empresa->id, 403);
        abort_if($trabajador->estaCentralizado(), 422, 'No se puede eliminar: ya fue centralizado.');

        $trabajador->delete();

        return back()->with('status', 'Registro eliminado.');
    }

    public function centralizar(Request $request, Empresa $empresa, CentralizacionRemuneracionesService $servicio)
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
