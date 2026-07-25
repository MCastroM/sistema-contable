<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\SaldoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BalanceComprobacionController extends Controller
{
    /**
     * Balance de comprobación (8 columnas): sumas y saldos de
     * todas las cuentas con movimiento en el período, agrupadas
     * por clase, con el gran total de control al final.
     */
    public function index(Request $request, SaldoService $saldos)
    {
        $empresa = $this->empresaActiva();

        $desde = $request->filled('desde')
            ? Carbon::parse($request->desde)
            : now()->startOfYear();

        $hasta = $request->filled('hasta')
            ? Carbon::parse($request->hasta)
            : now()->endOfYear();

        // Reutilizamos el índice del mayor: ya trae saldo anterior,
        // sumas del período y saldo final por cuenta.
        $filas = $saldos->indice($empresa, $desde, $hasta);

        // El balance de 8 columnas parte del saldo ANTERIOR + sumas,
        // no del saldo final directo, para que el lector vea el
        // acumulado completo (arrastre incluido) como "saldo".
        $porClase = $filas
            ->map(function ($f) {
                // saldoFinal viene AJUSTADO por naturaleza (positivo = lado
                // esperado). Para las columnas deudor/acreedor del balance
                // de 8 columnas necesitamos el signo REAL contable
                // (debe acumulado - haber acumulado), sin ese ajuste:
                //   cuenta deudora  -> saldoFinal YA es debe - haber
                //   cuenta acreedora-> saldoFinal es haber - debe, así que
                //                      el real es el inverso
                $real = $f->esDeudora ? $f->saldoFinal : bcmul($f->saldoFinal, '-1', 2);

                $saldoDeudor   = bccomp($real, '0', 2) === 1  ? $real : '0';
                $saldoAcreedor = bccomp($real, '0', 2) === -1 ? bcmul($real, '-1', 2) : '0';

                return (object) array_merge((array) $f, [
                    'saldoDeudor'   => $saldoDeudor,
                    'saldoAcreedor' => $saldoAcreedor,
                ]);
            })
            ->groupBy(fn ($f) => $f->cuenta->clase);

        // Totales de control (deben cuadrar en las 4 columnas de a pares)
        $totales = [
            'debe'          => $filas->reduce(fn ($a, $f) => bcadd($a, $f->debe, 2), '0'),
            'haber'         => $filas->reduce(fn ($a, $f) => bcadd($a, $f->haber, 2), '0'),
            'saldoDeudor'   => $porClase->flatten(1)->reduce(fn ($a, $f) => bcadd($a, $f->saldoDeudor, 2), '0'),
            'saldoAcreedor' => $porClase->flatten(1)->reduce(fn ($a, $f) => bcadd($a, $f->saldoAcreedor, 2), '0'),
        ];

        return view('reportes.balance-comprobacion', [
            'empresa'   => $empresa,
            'porClase'  => $porClase,
            'totales'   => $totales,
            'desde'     => $desde,
            'hasta'     => $hasta,
            'cantidad'  => $filas->count(),
        ]);
    }

    private function empresaActiva(): Empresa
    {
        $empresa = Empresa::find(session('empresa_activa_id'))
            ?? Empresa::where('activa', true)->orderBy('razon_social')->first();

        abort_if(! $empresa, 404, 'No hay empresas registradas.');

        return $empresa;
    }
}
