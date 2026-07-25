<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\DocumentoCompra;
use App\Models\Empresa;
use App\Models\MapeoCuenta;
use App\Services\ImportadorComprasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportacionComprasController extends Controller
{
    public function form(Empresa $empresa)
    {
        return view('compras.importar', compact('empresa'));
    }

    /**
     * Paso 1: sube el archivo, lo guarda temporalmente, y si hay
     * proveedores sin mapeo, muestra el formulario para resolverlos.
     * Si todos ya estaban mapeados, importa directo.
     */
    public function previsualizar(Request $request, Empresa $empresa, ImportadorComprasService $importador)
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

        // Guardar el archivo temporalmente para no perderlo entre pasos
        $rutaRelativa = $request->file('archivo')->store('importaciones/compras');
        $rutaAbsoluta = Storage::path($rutaRelativa);

        $filas = $importador->leerFilas($rutaAbsoluta);

        if ($filas->isEmpty()) {
            Storage::delete($rutaRelativa);
            return back()->withErrors(['archivo' => 'El archivo no contiene filas reconocibles.']);
        }

        $pendientes = $importador->proveedoresSinMapeo($empresa, $filas);

        if ($pendientes->isEmpty()) {
            // Todos los proveedores ya tienen cuenta asignada: importar directo
            $total = $this->importarFilas($empresa, $periodo, $validated['mes'], $filas);
            Storage::delete($rutaRelativa);
            return redirect()
                ->route('compras.index', ['empresa' => $empresa, 'anio' => $validated['anio'], 'mes' => $validated['mes']])
                ->with('status', "{$total} documentos importados.");
        }

        $cuentasImputables = $empresa->cuentas()->imputables()->orderBy('codigo')->get(['id', 'codigo', 'nombre']);

        return view('mapeo.resolver', [
            'empresa' => $empresa, 'anio' => $validated['anio'], 'mes' => $validated['mes'],
            'rutaArchivo' => $rutaRelativa, 'pendientes' => $pendientes,
            'cuentas' => $cuentasImputables, 'totalFilas' => $filas->count(),
        ]);
    }

    /**
     * Paso 2: guarda el mapeo elegido para los proveedores pendientes
     * y ejecuta la importación completa.
     */
    public function confirmar(Request $request, Empresa $empresa, ImportadorComprasService $importador)
    {
        $validated = $request->validate([
            'anio'        => ['required', 'integer'],
            'mes'         => ['required', 'integer'],
            'ruta_archivo'=> ['required', 'string'],
            'mapeo'       => ['required', 'array'],
            'mapeo.*.rut' => ['required', 'string'],
            'mapeo.*.razon_social' => ['nullable', 'string'],
            'mapeo.*.cuenta_id'    => ['required', 'exists:cuentas,id'],
        ]);

        $periodo = $empresa->periodo($validated['anio']);
        abort_if(! $periodo, 404);

        foreach ($validated['mapeo'] as $m) {
            MapeoCuenta::updateOrCreate(
                ['empresa_id' => $empresa->id, 'libro' => ImportadorComprasService::LIBRO, 'codigo_origen' => $m['rut']],
                ['nombre_origen' => $m['razon_social'] ?? null, 'cuenta_id' => $m['cuenta_id']],
            );
        }

        $rutaAbsoluta = Storage::path($validated['ruta_archivo']);
        $filas = $importador->leerFilas($rutaAbsoluta);
        $total = $this->importarFilas($empresa, $periodo, $validated['mes'], $filas);
        Storage::delete($validated['ruta_archivo']);

        return redirect()
            ->route('compras.index', ['empresa' => $empresa, 'anio' => $validated['anio'], 'mes' => $validated['mes']])
            ->with('status', "{$total} documentos importados.");
    }

    /** Inserta las filas ya resueltas (con o sin cuenta_gasto asignada). */
    private function importarFilas(Empresa $empresa, $periodo, int $mes, $filas): int
    {
        $mapeos = MapeoCuenta::where('empresa_id', $empresa->id)
            ->where('libro', ImportadorComprasService::LIBRO)
            ->pluck('cuenta_id', 'codigo_origen');

        $creados = 0;
        foreach ($filas as $f) {
            DocumentoCompra::updateOrCreate(
                ['empresa_id' => $empresa->id, 'periodo_id' => $periodo->id, 'mes' => $mes, 'nro' => $f['nro']],
                [
                    'tipo_dte' => $f['tipo'], 'rut_proveedor' => $f['rut'], 'razon_social' => $f['razon_social'],
                    'folio' => $f['folio'], 'fecha' => $f['fecha'] ?: now()->toDateString(),
                    'exento' => $f['exento'], 'neto' => $f['neto'], 'iva' => $f['iva'], 'total' => $f['total'],
                    'cuenta_gasto_id' => $mapeos->get($f['rut']),
                ],
            );
            $creados++;
        }

        return $creados;
    }
}
