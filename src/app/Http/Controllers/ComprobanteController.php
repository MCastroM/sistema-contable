<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Empresa;
use App\Models\Movimiento;
use App\Services\ComprobanteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprobanteController extends Controller
{
    /**
     * Formulario de edición: TODAS las líneas del comprobante juntas,
     * se editan y se guardan de una sola vez. Permitido en cualquier
     * estado (borrador o aprobado) -- si ya esta aprobado, el formulario
     * exige una confirmacion extra antes de guardar.
     */
    public function edit(Comprobante $comprobante)
    {
        $this->verificarEmpresa($comprobante);
        $empresa = $this->empresaActiva();

        $comprobante->load(['movimientos.cuenta', 'movimientos.centroCosto']);

        $cuentas = $empresa->cuentas()
            ->imputables()
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre']);

        return view('comprobantes.edit', compact('comprobante', 'cuentas'));
    }

    /**
     * Guarda los cambios de TODAS las lineas de una vez. Solo edita
     * lineas EXISTENTES (cuenta/debe/haber/glosa) -- no agrega ni
     * elimina lineas en esta version. Valida que quede cuadrado antes
     * de confirmar el guardado (todo o nada, en una transaccion).
     */
    public function update(Request $request, Comprobante $comprobante)
    {
        $this->verificarEmpresa($comprobante);

        // Si el comprobante YA esta aprobado, exigimos una confirmacion
        // explicita adicional -- editar un asiento ya aprobado altera
        // historia contable ya cerrada, y no debe hacerse "sin querer".
        if ($comprobante->estado === 'aprobado') {
            $request->validate([
                'confirmar_aprobado' => ['required', 'accepted'],
            ], [
                'confirmar_aprobado.required' => 'Debes marcar la casilla de confirmación para editar un comprobante ya aprobado.',
                'confirmar_aprobado.accepted' => 'Debes marcar la casilla de confirmación para editar un comprobante ya aprobado.',
            ]);
        }

        $validated = $request->validate([
            'lineas'              => ['required', 'array', 'min:2'],
            'lineas.*.id'         => ['required', 'integer'],
            'lineas.*.cuenta_id'  => ['required', 'integer', 'exists:cuentas,id'],
            'lineas.*.debe'       => ['nullable', 'numeric', 'min:0'],
            'lineas.*.haber'      => ['nullable', 'numeric', 'min:0'],
            'lineas.*.glosa'      => ['nullable', 'string', 'max:300'],
        ]);

        // Seguridad: las lineas enviadas deben pertenecer a ESTE comprobante,
        // ni una mas, ni una menos que las que realmente tiene.
        $idsReales = $comprobante->movimientos()->pluck('id')->sort()->values();
        $idsEnviados = collect($validated['lineas'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

        if (! $idsReales->diff($idsEnviados)->isEmpty() || ! $idsEnviados->diff($idsReales)->isEmpty()) {
            return back()->withInput()->withErrors([
                'accion' => 'Las líneas enviadas no coinciden con las del comprobante. Recarga la página e intenta de nuevo.',
            ]);
        }

        try {
            DB::transaction(function () use ($validated, $comprobante) {
                foreach ($validated['lineas'] as $linea) {
                    Movimiento::where('id', $linea['id'])
                        ->where('comprobante_id', $comprobante->id)
                        ->update([
                            'cuenta_id' => $linea['cuenta_id'],
                            'debe'      => $linea['debe'] ?? 0,
                            'haber'     => $linea['haber'] ?? 0,
                            'glosa'     => $linea['glosa'] ?? null,
                        ]);
                }

                // Verificar cuadratura DESPUES de guardar -- si no cuadra,
                // se revierte todo (la transaccion hace rollback al lanzar).
                $suma = DB::table('movimientos')->where('comprobante_id', $comprobante->id)
                    ->selectRaw('SUM(debe) as d, SUM(haber) as h')->first();

                if (round((float) $suma->d, 2) !== round((float) $suma->h, 2)) {
                    throw new \RuntimeException(
                        'El comprobante quedaría descuadrado (debe=' . number_format($suma->d, 2) .
                        ', haber=' . number_format($suma->h, 2) . '). Corrige los montos antes de guardar.'
                    );
                }
            });

            return redirect()->route('comprobantes.show', $comprobante)
                ->with('status', "Comprobante {$comprobante->folio()} actualizado.");

        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['accion' => $e->getMessage()]);
        }
    }

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
