<?php

namespace App\Http\Controllers;

use App\Models\DocumentoCompra;
use App\Models\Empresa;
use App\Services\CentralizacionComprasService;
use Illuminate\Http\Request;

class DocumentoCompraController extends Controller
{
    /** Listado de documentos de compra de un período+mes de la empresa. */
    public function index(Request $request, Empresa $empresa)
    {
        $periodo = $empresa->periodo($request->integer('anio', now()->year));
        $mes = $request->integer('mes', now()->month);

        $documentos = collect();
        if ($periodo) {
            $documentos = DocumentoCompra::where('empresa_id', $empresa->id)
                ->where('periodo_id', $periodo->id)
                ->where('mes', $mes)
                ->with('cuentaGasto:id,codigo,nombre')
                ->orderBy('nro')
                ->get();
        }

        return view('compras.index', [
            'empresa' => $empresa, 'periodo' => $periodo, 'mes' => $mes,
            'documentos' => $documentos,
            'anio' => $request->integer('anio', now()->year),
        ]);
    }

    /** Alta manual de un documento (sin pasar por importación). */
    public function store(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'anio'          => ['required', 'integer'],
            'mes'           => ['required', 'integer', 'min:1', 'max:12'],
            'nro'           => ['required', 'integer', 'min:1'],
            'tipo_dte'      => ['required', 'string', 'max:5'],
            'rut_proveedor' => ['required', 'string', 'max:12'],
            'razon_social'  => ['required', 'string', 'max:200'],
            'folio'         => ['nullable', 'string', 'max:30'],
            'fecha'         => ['required', 'date'],
            'exento'        => ['nullable', 'numeric', 'min:0'],
            'neto'          => ['nullable', 'numeric', 'min:0'],
            'iva'           => ['nullable', 'numeric', 'min:0'],
            'total'         => ['required', 'numeric', 'min:0'],
            'cuenta_gasto_id' => ['nullable', 'exists:cuentas,id'],
        ]);

        $periodo = $empresa->periodo($validated['anio'])
            ?? throw \Illuminate\Validation\ValidationException::withMessages([
                'anio' => "No existe el período {$validated['anio']} para esta empresa.",
            ]);

        DocumentoCompra::create(array_merge($validated, [
            'empresa_id' => $empresa->id,
            'periodo_id' => $periodo->id,
        ]));

        return back()->with('status', 'Documento de compra agregado.');
    }

    public function destroy(Empresa $empresa, DocumentoCompra $documento)
    {
        abort_if($documento->empresa_id !== $empresa->id, 403);
        abort_if($documento->estaCentralizado(), 422, 'No se puede eliminar: ya fue centralizado.');

        $documento->delete();

        return back()->with('status', 'Documento eliminado.');
    }

    /** Asignar/editar la cuenta de gasto de un documento puntual. */
    public function asignarCuenta(Request $request, Empresa $empresa, DocumentoCompra $documento)
    {
        abort_if($documento->empresa_id !== $empresa->id, 403);
        abort_if($documento->estaCentralizado(), 422, 'No se puede editar: ya fue centralizado.');

        $validated = $request->validate(['cuenta_gasto_id' => ['required', 'exists:cuentas,id']]);
        $documento->update($validated);

        return back()->with('status', 'Cuenta asignada.');
    }

    /** Dispara la centralización del mes completo. */
    public function centralizar(Request $request, Empresa $empresa, CentralizacionComprasService $servicio)
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
