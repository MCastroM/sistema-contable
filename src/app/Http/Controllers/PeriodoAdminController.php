<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Periodo;
use App\Services\PeriodoService;
use Illuminate\Http\Request;

class PeriodoAdminController extends Controller
{
    /** Abre (crea) el período de un año para una empresa. */
    public function abrir(Request $request, Empresa $empresa, PeriodoService $servicio)
    {
        $validated = $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $periodo = $servicio->abrir($empresa, (int) $validated['anio']);

        return back()->with('status', "Período {$periodo->anio} abierto.");
    }

    /** Mueve la fecha de bloqueo (protege lo ya declarado). */
    public function bloquear(Request $request, Empresa $empresa, Periodo $periodo, PeriodoService $servicio)
    {
        $this->verificarEmpresa($empresa, $periodo);

        $validated = $request->validate([
            'fecha_bloqueo' => ['required', 'date'],
            'motivo'        => ['nullable', 'string', 'max:300'],
        ]);

        try {
            $servicio->bloquearHasta($periodo, $validated['fecha_bloqueo'], $validated['motivo'] ?? null);
            return back()->with('status', "Bloqueo movido al {$validated['fecha_bloqueo']}.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }

    /** Cierre del año: exige que no queden borradores pendientes. */
    public function cerrar(Request $request, Empresa $empresa, Periodo $periodo, PeriodoService $servicio)
    {
        $this->verificarEmpresa($empresa, $periodo);

        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:300'],
        ]);

        try {
            $servicio->cerrar($periodo, $validated['motivo'] ?? null);
            return back()->with('status', "Período {$periodo->anio} cerrado.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }

    /** Reapertura: motivo SIEMPRE obligatorio, queda en bitácora. */
    public function reabrir(Request $request, Empresa $empresa, Periodo $periodo, PeriodoService $servicio)
    {
        $this->verificarEmpresa($empresa, $periodo);

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:300'],
        ], [
            'motivo.required' => 'La reapertura de un período cerrado exige un motivo.',
            'motivo.min'      => 'Describe el motivo con al menos 10 caracteres.',
        ]);

        try {
            $servicio->reabrir($periodo, $validated['motivo']);
            return back()->with('status', "Período {$periodo->anio} reabierto. Motivo registrado en bitácora.");
        } catch (\Throwable $e) {
            return back()->withErrors(['accion' => $e->getMessage()]);
        }
    }

    /** Seguridad: el período debe pertenecer a la empresa de la URL. */
    private function verificarEmpresa(Empresa $empresa, Periodo $periodo): void
    {
        abort_if($periodo->empresa_id !== $empresa->id, 403,
            'El período no pertenece a esta empresa.');
    }
}
