<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\Empresa;
use Illuminate\Http\Request;

class CuentaController extends Controller
{
    /**
     * Árbol del plan de cuentas de la empresa activa.
     */
    public function index()
    {
        $empresa = $this->empresaActiva();
        if (! $empresa) {
            return redirect()->route('dashboard');
        }

        // Una sola consulta trae TODO el plan; el árbol se arma en memoria.
        // (121 filas: liviano. Con eager loading recursivo serían N consultas.)
        $cuentas = $empresa->cuentas()->orderBy('codigo')->get();

        // Agrupar por padre: $porPadre[null] = raíces, $porPadre[id] = hijas de id
        $porPadre = $cuentas->groupBy('padre_id');

        $totales = [
            'total'      => $cuentas->count(),
            'imputables' => $cuentas->where('imputable', true)->count(),
        ];

        return view('cuentas.index', [
            'empresa'  => $empresa,
            'raices'   => $porPadre->get('') ?? $porPadre->get(null) ?? collect(),
            'porPadre' => $porPadre,
            'totales'  => $totales,
        ]);
    }

    public function create()
    {
        $empresa = $this->empresaActiva();
        if (! $empresa) {
            return redirect()->route('dashboard');
        }

        $cuentasPadre = $empresa->cuentas()->orderBy('codigo')->get(['id', 'codigo', 'nombre']);

        return view('cuentas.create', compact('empresa', 'cuentasPadre'));
    }

    public function store(Request $request)
    {
        $empresa = $this->empresaActiva();
        if (! $empresa) {
            return redirect()->route('dashboard');
        }

        $validated = $this->validarDatos($request, $empresa);

        $cuenta = $empresa->cuentas()->create(array_merge($validated, ['activa' => true]));

        return redirect()->route('cuentas.index')->with('status', "Cuenta {$cuenta->codigo} creada.");
    }

    public function edit(Cuenta $cuenta)
    {
        $empresa = $this->empresaActiva();
        abort_if(! $empresa || $cuenta->empresa_id !== $empresa->id, 403);

        $cuentasPadre = $empresa->cuentas()->where('id', '!=', $cuenta->id)->orderBy('codigo')->get(['id', 'codigo', 'nombre']);
        $tieneMovimientos = \App\Models\Movimiento::where('cuenta_id', $cuenta->id)->exists();

        return view('cuentas.edit', compact('empresa', 'cuenta', 'cuentasPadre', 'tieneMovimientos'));
    }

    public function update(Request $request, Cuenta $cuenta)
    {
        $empresa = $this->empresaActiva();
        abort_if(! $empresa || $cuenta->empresa_id !== $empresa->id, 403);

        $validated = $this->validarDatos($request, $empresa, $cuenta->id);

        $cuenta->update($validated);

        return redirect()->route('cuentas.index')->with('status', "Cuenta {$cuenta->codigo} actualizada.");
    }

    /**
     * Las cuentas NUNCA se eliminan de verdad (podrian tener movimientos
     * historicos referenciandolas) -- solo se desactivan, para que dejen
     * de aparecer como opcion en formularios nuevos sin perder el historial.
     */
    public function toggleActiva(Cuenta $cuenta)
    {
        $empresa = $this->empresaActiva();
        abort_if(! $empresa || $cuenta->empresa_id !== $empresa->id, 403);

        $cuenta->update(['activa' => ! $cuenta->activa]);

        $estado = $cuenta->activa ? 'activada' : 'desactivada';

        return back()->with('status', "Cuenta {$cuenta->codigo} {$estado}.");
    }

    private function validarDatos(Request $request, Empresa $empresa, ?int $ignorarId = null): array
    {
        $validated = $request->validate([
            'codigo' => [
                'required', 'string', 'max:20',
                function ($attribute, $value, $fail) use ($empresa, $ignorarId) {
                    $existe = $empresa->cuentas()->where('codigo', $value)
                        ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
                        ->exists();
                    if ($existe) {
                        $fail("Ya existe una cuenta con el código {$value} en esta empresa.");
                    }
                },
            ],
            'nombre'    => ['required', 'string', 'max:150'],
            'clase'     => ['required', 'in:activo,pasivo,patrimonio,resultado'],
            'padre_id'  => ['nullable', 'exists:cuentas,id'],
        ]);

        // Los checkboxes sin marcar no llegan en el request -- se lee aparte.
        $validated['imputable'] = $request->boolean('imputable');

        return $validated;
    }

    private function empresaActiva(): ?Empresa
    {
        return Empresa::find(session('empresa_activa_id'))
            ?? Empresa::where('activa', true)->orderBy('razon_social')->first();
    }
}
