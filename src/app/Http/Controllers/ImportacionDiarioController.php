<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\MapeoCuenta;
use App\Services\EjecutorImportacionDiarioService;
use App\Services\ImportadorDiarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportacionDiarioController extends Controller
{
    public function form(Empresa $empresa)
    {
        return view('diario.importar', compact('empresa'));
    }

    /**
     * Paso 1: sube el archivo, detecta fechas no interpretables y
     * códigos de cuenta sin mapeo. Si hay problemas, los muestra
     * ANTES de importar nada — mejor detenerse acá que a mitad
     * de un lote de cientos de asientos.
     */
    public function previsualizar(Request $request, Empresa $empresa, ImportadorDiarioService $importador)
    {
        $validated = $request->validate([
            'anio'    => ['required', 'integer'],
            'tipo'    => ['required', 'in:I,E,T'],
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $periodo = $empresa->periodo($validated['anio']);
        if (! $periodo) {
            return back()->withErrors(['anio' => "No existe el período {$validated['anio']}. Ábrelo primero."]);
        }

        $rutaRelativa = $request->file('archivo')->store('importaciones/diario');
        $rutaAbsoluta = Storage::path($rutaRelativa);

        $filas = $importador->leerFilas($rutaAbsoluta);

        if ($filas->isEmpty()) {
            Storage::delete($rutaRelativa);
            return back()->withErrors(['archivo' => 'El archivo no contiene filas reconocibles (revisa nombres de columnas).']);
        }

        // Fechas no interpretables: se muestran para que el usuario
        // sepa que esas líneas necesitan revisión en el Excel origen.
        $fechasInvalidas = $filas->filter(fn ($f) => $f['fecha'] === null)
            ->map(fn ($f) => ['comprobante' => $f['comprobante'], 'fecha_raw' => $f['fecha_raw']])
            ->unique('comprobante')->values();

        $pendientes = $importador->codigosSinMapeo($empresa, $filas);

        if ($pendientes->isEmpty() && $fechasInvalidas->isEmpty()) {
            return $this->ejecutar($empresa, $periodo, $validated['tipo'], $rutaRelativa, $filas);
        }

        $cuentasImputables = $empresa->cuentas()->imputables()->orderBy('codigo')->get(['id', 'codigo', 'nombre']);

        return view('diario.resolver', [
            'empresa' => $empresa, 'anio' => $validated['anio'], 'tipo' => $validated['tipo'],
            'rutaArchivo' => $rutaRelativa, 'pendientes' => $pendientes,
            'fechasInvalidas' => $fechasInvalidas, 'cuentas' => $cuentasImputables,
            'totalFilas' => $filas->count(), 'totalComprobantes' => $filas->pluck('comprobante')->unique()->count(),
        ]);
    }

    /** Paso 2: guarda el mapeo elegido y ejecuta la importación. */
    public function confirmar(Request $request, Empresa $empresa, ImportadorDiarioService $importador)
    {
        $validated = $request->validate([
            'anio'         => ['required', 'integer'],
            'tipo'         => ['required', 'in:I,E,T'],
            'ruta_archivo' => ['required', 'string'],
            'mapeo'        => ['nullable', 'array'],
            'mapeo.*.cod'       => ['required', 'string'],
            'mapeo.*.cuenta_id' => ['required', 'exists:cuentas,id'],
        ]);

        $periodo = $empresa->periodo($validated['anio']);
        abort_if(! $periodo, 404);

        foreach ($validated['mapeo'] ?? [] as $m) {
            MapeoCuenta::updateOrCreate(
                ['empresa_id' => $empresa->id, 'libro' => ImportadorDiarioService::LIBRO, 'codigo_origen' => $m['cod']],
                ['cuenta_id' => $m['cuenta_id']],
            );
        }

        $rutaAbsoluta = Storage::path($validated['ruta_archivo']);
        $filas = $importador->leerFilas($rutaAbsoluta);

        return $this->ejecutar($empresa, $periodo, $validated['tipo'], $validated['ruta_archivo'], $filas);
    }

    private function ejecutar(
        Empresa $empresa, $periodo, string $tipo, string $rutaArchivo, $filas
    ) {
        $importador = app(ImportadorDiarioService::class);
        $ejecutor = app(EjecutorImportacionDiarioService::class);

        // Solo las filas con fecha interpretable entran a la importación;
        // las demás quedan fuera y se reportan como "no importadas".
        $filasValidas = $filas->filter(fn ($f) => $f['fecha'] !== null);
        $filasConCuenta = $ejecutor->resolverCuentas($empresa, $filasValidas);

        $grupos = $importador->agruparPorComprobante($filasConCuenta);
        $resultado = $ejecutor->importarLote($empresa, $periodo, $tipo, $grupos);

        Storage::delete($rutaArchivo);

        $omitidos = $filas->count() - $filasValidas->count();

        return view('diario.resultado', [
            'empresa' => $empresa, 'periodo' => $periodo,
            'ok' => $resultado['ok'], 'errores' => $resultado['errores'], 'omitidos' => $omitidos,
        ]);
    }
}
