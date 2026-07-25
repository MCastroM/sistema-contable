<?php

namespace App\Http\Controllers;

use App\Models\BoletaHonorario;
use App\Models\Empresa;
use App\Models\MapeoCuenta;
use App\Services\ImportadorHonorariosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportacionHonorariosController extends Controller
{
    public function form(Empresa $empresa)
    {
        return view('honorarios.importar', compact('empresa'));
    }

    public function previsualizar(Request $request, Empresa $empresa, ImportadorHonorariosService $importador)
    {
        $validated = $request->validate([
            'anio'    => ['required', 'integer'],
            'mes'     => ['required', 'integer', 'min:1', 'max:12'],
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $periodo = $empresa->periodo($validated['anio']);
        if (! $periodo) {
            return back()->withErrors(['anio' => "No existe el período {$validated['anio']}. Ábrelo primero."]);
        }

        $rutaRelativa = $request->file('archivo')->store('importaciones/honorarios');
        $rutaAbsoluta = Storage::path($rutaRelativa);

        $filas = $importador->leerFilas($rutaAbsoluta);

        if ($filas->isEmpty()) {
            Storage::delete($rutaRelativa);
            return back()->withErrors(['archivo' => 'El archivo no contiene filas reconocibles.']);
        }

        $pendientes = $importador->prestadoresSinMapeo($empresa, $filas);

        if ($pendientes->isEmpty()) {
            $total = $this->importarFilas($empresa, $periodo, $validated['mes'], $filas);
            Storage::delete($rutaRelativa);
            return redirect()
                ->route('honorarios.index', ['empresa' => $empresa, 'anio' => $validated['anio'], 'mes' => $validated['mes']])
                ->with('status', "{$total} boletas importadas.");
        }

        $cuentasImputables = $empresa->cuentas()->imputables()->orderBy('codigo')->get(['id', 'codigo', 'nombre']);

        return view('mapeo.resolver-honorarios', [
            'empresa' => $empresa, 'anio' => $validated['anio'], 'mes' => $validated['mes'],
            'rutaArchivo' => $rutaRelativa, 'pendientes' => $pendientes,
            'cuentas' => $cuentasImputables, 'totalFilas' => $filas->count(),
        ]);
    }

    public function confirmar(Request $request, Empresa $empresa, ImportadorHonorariosService $importador)
    {
        $validated = $request->validate([
            'anio'         => ['required', 'integer'],
            'mes'          => ['required', 'integer'],
            'ruta_archivo' => ['required', 'string'],
            'mapeo'        => ['required', 'array'],
            'mapeo.*.rut'    => ['required', 'string'],
            'mapeo.*.nombre' => ['nullable', 'string'],
            'mapeo.*.cuenta_id' => ['required', 'exists:cuentas,id'],
        ]);

        $periodo = $empresa->periodo($validated['anio']);
        abort_if(! $periodo, 404);

        foreach ($validated['mapeo'] as $m) {
            MapeoCuenta::updateOrCreate(
                ['empresa_id' => $empresa->id, 'libro' => ImportadorHonorariosService::LIBRO, 'codigo_origen' => $m['rut']],
                ['nombre_origen' => $m['nombre'] ?? null, 'cuenta_id' => $m['cuenta_id']],
            );
        }

        $rutaAbsoluta = Storage::path($validated['ruta_archivo']);
        $filas = $importador->leerFilas($rutaAbsoluta);
        $total = $this->importarFilas($empresa, $periodo, $validated['mes'], $filas);
        Storage::delete($validated['ruta_archivo']);

        return redirect()
            ->route('honorarios.index', ['empresa' => $empresa, 'anio' => $validated['anio'], 'mes' => $validated['mes']])
            ->with('status', "{$total} boletas importadas.");
    }

    private function importarFilas(Empresa $empresa, $periodo, int $mes, $filas): int
    {
        $mapeos = MapeoCuenta::where('empresa_id', $empresa->id)
            ->where('libro', ImportadorHonorariosService::LIBRO)
            ->pluck('cuenta_id', 'codigo_origen');

        $creados = 0;
        foreach ($filas as $f) {
            BoletaHonorario::updateOrCreate(
                ['empresa_id' => $empresa->id, 'periodo_id' => $periodo->id, 'mes' => $mes, 'nro' => $f['nro']],
                [
                    'boleta' => $f['boleta'], 'fecha' => $f['fecha'] ?: now()->toDateString(),
                    'rut_prestador' => $f['rut'], 'nombre_prestador' => $f['nombre'],
                    'brutos' => $f['brutos'], 'retencion' => $f['retencion'], 'total' => $f['total'],
                    'cuenta_gasto_id' => $mapeos->get($f['rut']),
                ],
            );
            $creados++;
        }

        return $creados;
    }
}
