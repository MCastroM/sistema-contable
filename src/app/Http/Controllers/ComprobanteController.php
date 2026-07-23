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
            ->withSum('movimientos as total_debe', 'debe')
            ->with('periodo')
            ->when($request->filled('estado'),
                fn ($q) => $q->where('estado', $request->estado))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

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
     * Formulario de creación: entrega las cuentas imputables
     * de la empresa activa para los selectores de línea.
     */
    public function create()
    {
        $empresa = $this->empresaActiva();

        $cuentas = $empresa->cuentas()
            ->imputables()
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre']);

        return view('comprobantes.create', compact('empresa', 'cuentas'));
    }

    /**
     * Recibe el formulario y delega la creación al servicio.
     * El JavaScript del formulario AYUDA a cuadrar; las reglas
     * de verdad las aplica el servicio (y la base) como siempre.
     */
    public function store(Request $request, ComprobanteService $servicio)
    {
        $empresa = $this->empresaActiva();

        $validated = $request->validate([
            'tipo'               => ['required', 'in:I,E,T'],
            'fecha'              => ['required', 'date'],
            'glosa'              => ['required', 'string', 'min:3', 'max:300'],
            'lineas'             => ['required', 'array', 'min:2'],
            'lineas.*.cuenta_id' => ['required', 'integer'],
            'lineas.*.debe'      => ['nullable', 'numeric', 'min:0'],
            'lineas.*.haber'     => ['nullable', 'numeric', 'min:0'],
            'lineas.*.glosa'     => ['nullable', 'string', 'max:300'],
        ], [
            'lineas.min'      => 'Un comprobante requiere al menos 2 líneas.',
            'lineas.required' => 'Agrega las líneas del asiento.',
        ]);

        // Normalizar: nulls a 0
        $lineas = collect($validated['lineas'])->map(fn ($l) => [
            'cuenta_id' => (int) $l['cuenta_id'],
            'debe'      => $l['debe'] ?? 0,
            'haber'     => $l['haber'] ?? 0,
            'glosa'     => $l['glosa'] ?? null,
        ])->all();

        try {
            $comprobante = $servicio->crearBorrador(
                $empresa,
                $validated['tipo'],
                $validated['fecha'],
                $validated['glosa'],
                $lineas,
            );

            return redirect()
                ->route('comprobantes.show', $comprobante)
                ->with('status', "Borrador {$comprobante->folio()} creado. Revísalo y apruébalo cuando corresponda.");

        } catch (\Throwable $e) {
            // withInput conserva lo escrito para no perder el trabajo
            return back()->withInput()->withErrors(['accion' => $e->getMessage()]);
        }
    }

    /**
     * Detalle de un comprobante con sus líneas.
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

    /** Seguridad multiempresa: 403 si el comprobante es de otra empresa. */
    private function verificarEmpresa(Comprobante $comprobante): void
    {
        abort_if(
            $comprobante->empresa_id !== $this->empresaActiva()->id,
            403,
            'El comprobante pertenece a otra empresa.'
        );
    }
}
