<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Empresa;
use App\Services\ComprobanteService;
use Illuminate\Http\Request;

class ComprobanteController extends Controller
{
    /**
     * Listado de comprobantes de la empresa activa,
     * con filtro opcional por estado (?estado=borrador).
     */
    public function index(Request $request)
    {
        $empresa = $this->empresaActiva();

        $comprobantes = $empresa->comprobantes()
            // withSum evita el problema "N+1": calcula el total de cada
            // comprobante EN LA MISMA consulta, en vez de una consulta extra
            // por cada fila de la tabla.
            ->withSum('movimientos as total_debe', 'debe')
            ->with('periodo')
            ->when($request->filled('estado'),
                fn ($q) => $q->where('estado', $request->estado))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();   // conserva ?estado=... al cambiar de página

        return view('comprobantes.index', [
            'empresa'       => $empresa,
            'comprobantes'  => $comprobantes,
            'estadoActivo'  => $request->estado,
            'conteos'       => [
                'todos'    => $empresa->comprobantes()->count(),
                'borrador' => $empresa->comprobantes()->where('estado', 'borrador')->count(),
                'aprobado' => $empresa->comprobantes()->where('estado', 'aprobado')->count(),
                'anulado'  => $empresa->comprobantes()->where('estado', 'anulado')->count(),
            ],
        ]);
    }

    /**
     * Detalle de un comprobante con sus líneas.
     * Laravel inyecta el Comprobante automáticamente desde la URL
     * (route model binding): /comprobantes/8 -> findOrFail(8).
     */
    public function show(Comprobante $comprobante)
    {
        $this->verificarEmpresa($comprobante);

        $comprobante->load(['movimientos.cuenta', 'movimientos.centroCosto',
                            'periodo', 'creadoPor', 'aprobadoPor']);

        return view('comprobantes.show', compact('comprobante'));
    }

    /** Aprobar un borrador (valida cuadratura vía servicio). */
    public function aprobar(Comprobante $comprobante, ComprobanteService $servicio)
    {
        $this->verificarEmpresa($comprobante);

        try {
            $servicio->aprobar($comprobante);
            return back()->with('status', "Comprobante {$comprobante->folio()} aprobado.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }

    /** Anular un aprobado (motivo obligatorio). */
    public function anular(Request $request, Comprobante $comprobante, ComprobanteService $servicio)
    {
        $this->verificarEmpresa($comprobante);

        $validated = $request->validate(
            ['motivo' => ['required', 'string', 'min:5', 'max:500']],
            ['motivo.required' => 'La anulación exige un motivo.',
             'motivo.min'      => 'El motivo debe tener al menos 5 caracteres.'],
        );

        try {
            $servicio->anular($comprobante, $validated['motivo']);
            return back()->with('status', "Comprobante {$comprobante->folio()} anulado.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }

    /** Eliminar un borrador (físico, con bitácora). */
    public function eliminar(Comprobante $comprobante, ComprobanteService $servicio)
    {
        $this->verificarEmpresa($comprobante);
        $folio = $comprobante->folio();

        try {
            $servicio->eliminarBorrador($comprobante);
            return redirect()->route('comprobantes.index')
                ->with('status', "Borrador {$folio} eliminado.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }

    /** Empresa activa de la sesión (mismo criterio que el dashboard). */
    private function empresaActiva(): Empresa
    {
        $empresa = Empresa::find(session('empresa_activa_id'))
            ?? Empresa::where('activa', true)->orderBy('razon_social')->first();

        abort_if(! $empresa, 404, 'No hay empresas registradas.');

        return $empresa;
    }

    /**
     * Seguridad multiempresa: un comprobante de OTRA empresa
     * no se puede ni ver ni tocar desde esta sesión.
     */
    private function verificarEmpresa(Comprobante $comprobante): void
    {
        abort_if(
            $comprobante->empresa_id !== $this->empresaActiva()->id,
            403,
            'El comprobante pertenece a otra empresa.'
        );
    }
}
