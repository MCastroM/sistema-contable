<?php

namespace App\Http\Controllers;

use App\Models\DocumentoVenta;
use App\Models\Empresa;
use App\Services\CentralizacionVentasService;
use Illuminate\Http\Request;

class DocumentoVentaController extends Controller
{
    public function index(Request $request, Empresa $empresa)
    {
        $periodo = $empresa->periodo($request->integer('anio', now()->year));
        $mes = $request->integer('mes', now()->month);

        $documentos = collect();
        if ($periodo) {
            $documentos = DocumentoVenta::where('empresa_id', $empresa->id)
                ->where('periodo_id', $periodo->id)
                ->where('mes', $mes)
                ->with('cuentaIngreso:id,codigo,nombre')
                ->orderBy('nro')
                ->get();
        }

        return view('ventas.index', [
            'empresa' => $empresa, 'periodo' => $periodo, 'mes' => $mes,
            'documentos' => $documentos,
            'anio' => $request->integer('anio', now()->year),
        ]);
    }

    public function store(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'anio'          => ['required', 'integer'],
            'mes'           => ['required', 'integer', 'min:1', 'max:12'],
            'nro'           => ['required', 'integer', 'min:1'],
            'tipo_dte'      => ['required', 'string', 'max:5'],
            'rut_cliente'   => ['required', 'string', 'max:12'],
            'razon_social'  => ['required', 'string', 'max:200'],
            'folio'         => ['nullable', 'string', 'max:30'],
            'fecha'         => ['required', 'date'],
            'exento'        => ['nullable', 'numeric', 'min:0'],
            'neto'          => ['nullable', 'numeric', 'min:0'],
            'iva'           => ['nullable', 'numeric', 'min:0'],
            'total'         => ['required', 'numeric', 'min:0'],
            'cuenta_ingreso_id' => ['nullable', 'exists:cuentas,id'],
        ]);

        $periodo = $empresa->periodo($validated['anio'])
            ?? throw \Illuminate\Validation\ValidationException::withMessages([
                'anio' => "No existe el período {$validated['anio']} para esta empresa.",
            ]);

        DocumentoVenta::create(array_merge($validated, [
            'empresa_id' => $empresa->id,
            'periodo_id' => $periodo->id,
        ]));

        return back()->with('status', 'Documento de venta agregado.');
    }

    public function destroy(Empresa $empresa, DocumentoVenta $documento)
    {
        abort_if($documento->empresa_id !== $empresa->id, 403);
        abort_if($documento->estaCentralizado(), 422, 'No se puede eliminar: ya fue centralizado.');

        $documento->delete();

        return back()->with('status', 'Documento eliminado.');
    }

    public function asignarCuenta(Request $request, Empresa $empresa, DocumentoVenta $documento)
    {
        abort_if($documento->empresa_id !== $empresa->id, 403);
        abort_if($documento->estaCentralizado(), 422, 'No se puede editar: ya fue centralizado.');

        $validated = $request->validate(['cuenta_ingreso_id' => ['required', 'exists:cuentas,id']]);
        $documento->update($validated);

        return back()->with('status', 'Cuenta asignada.');
    }

    public function centralizar(Request $request, Empresa $empresa, CentralizacionVentasService $servicio)
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
