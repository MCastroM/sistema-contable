<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\Empresa;
use App\Services\SaldoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LibroMayorController extends Controller
{
    /**
     * Índice del mayor: cuentas con movimiento en el período,
     * con saldo anterior, débitos, créditos y saldo final.
     */
    public function index(Request $request, SaldoService $saldos)
    {
        $empresa = $this->empresaActiva();
        [$desde, $hasta] = $this->rango($request);

        $filas = $saldos->indice($empresa, $desde, $hasta);

        // Totales de control: la suma de débitos debe igualar la de créditos
        $totalDebe  = $filas->reduce(fn ($acc, $f) => bcadd($acc, $f->debe,  2), '0');
        $totalHaber = $filas->reduce(fn ($acc, $f) => bcadd($acc, $f->haber, 2), '0');

        return view('reportes.libro-mayor', compact(
            'empresa', 'filas', 'desde', 'hasta', 'totalDebe', 'totalHaber'
        ));
    }

    /**
     * Detalle de UNA cuenta con saldo corriente línea a línea.
     */
    public function cuenta(Request $request, Cuenta $cuenta, SaldoService $saldos)
    {
        $empresa = $this->empresaActiva();

        abort_if($cuenta->empresa_id !== $empresa->id, 403,
            'La cuenta pertenece a otra empresa.');

        [$desde, $hasta] = $this->rango($request);

        $detalle = $saldos->detalle($cuenta, $desde, $hasta);

        return view('reportes.libro-mayor-cuenta', compact(
            'empresa', 'cuenta', 'detalle', 'desde', 'hasta'
        ));
    }

    /** Rango de fechas: por defecto, el año en curso completo. */
    private function rango(Request $request): array
    {
        return [
            $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfYear(),
            $request->filled('hasta') ? Carbon::parse($request->hasta) : now()->endOfYear(),
        ];
    }

    private function empresaActiva(): Empresa
    {
        $empresa = Empresa::find(session('empresa_activa_id'))
            ?? Empresa::where('activa', true)->orderBy('razon_social')->first();

        abort_if(! $empresa, 404, 'No hay empresas registradas.');

        return $empresa;
    }
}
