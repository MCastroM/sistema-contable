<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\RemuneracionTrabajador;
use App\Services\ImportadorRemuneracionesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportacionRemuneracionesController extends Controller
{
    public function form(Empresa $empresa)
    {
        return view('remuneraciones.importar', compact('empresa'));
    }

    /**
     * Sin mapeo de cuentas: todos los trabajadores centralizan a las
     * mismas cuentas fijas de la empresa. Se importa directo.
     */
    public function importar(Request $request, Empresa $empresa, ImportadorRemuneracionesService $importador)
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

        $rutaRelativa = $request->file('archivo')->store('importaciones/remuneraciones');
        $rutaAbsoluta = Storage::path($rutaRelativa);

        $filas = $importador->leerFilas($rutaAbsoluta);
        Storage::delete($rutaRelativa);

        if ($filas->isEmpty()) {
            return back()->withErrors(['archivo' => 'El archivo no contiene filas reconocibles.']);
        }

        $creados = 0;
        foreach ($filas as $f) {
            RemuneracionTrabajador::updateOrCreate(
                ['empresa_id' => $empresa->id, 'periodo_id' => $periodo->id, 'mes' => $validated['mes'], 'nro' => $f['nro']],
                [
                    'rut_trabajador' => $f['rut'], 'nombre_trabajador' => $f['nombre'],
                    'sueldo' => $f['sueldo'], 'gratificacion' => $f['gratificacion'],
                    'movilizacion' => $f['movilizacion'], 'colacion' => $f['colacion'],
                    'otros_haberes' => $f['otros_haberes'], 'produccion' => $f['produccion'],
                    'total_haberes' => $f['total_haberes'], 'afp' => $f['afp'], 'salud' => $f['salud'],
                    'pactado_salud' => $f['pactado_salud'], 'cesantia' => $f['cesantia'],
                    'impuesto_unico' => $f['impuesto_unico'], 'prestamo' => $f['prestamo'],
                    'cuenta_ahorro' => $f['cuenta_ahorro'], 'anticipo' => $f['anticipo'],
                    'liquido' => $f['liquido'],
                ],
            );
            $creados++;
        }

        return redirect()
            ->route('remuneraciones.index', ['empresa' => $empresa, 'anio' => $validated['anio'], 'mes' => $validated['mes']])
            ->with('status', "{$creados} trabajador(es) importados.");
    }
}
