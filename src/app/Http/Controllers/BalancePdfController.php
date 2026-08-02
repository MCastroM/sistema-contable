<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\SaldoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BalancePdfController extends Controller
{
    /**
     * Balance Tributario de 8 columnas en PDF, leyendo EN VIVO desde la BD.
     *
     * Columnas SUMAS (Debe/Haber):
     *  - Cuentas de BALANCE (activo/pasivo/patrimonio): el SALDO DE APERTURA
     *    neto se incorpora al lado que corresponde por naturaleza (deudor->
     *    DEBE, acreedor->HABER) y se le SUMA el movimiento del año. Así las
     *    cuentas sin movimiento igual muestran su saldo arrastrado, y los
     *    saldos "cuadran visiblemente".
     *  - Cuentas de RESULTADO: solo el movimiento del año (arrancan en cero
     *    cada ejercicio por el cierre anterior; sin apertura).
     *
     * Columnas SALDOS / INVENTARIO / RESULTADO: saldo neto según naturaleza.
     *
     * ?pre_cierre=1 : excluye el asiento "CIERRE DEL EJERCICIO {año}" del año
     * en curso (según 'hasta'). Los cierres de años anteriores sí cuentan.
     */
    public function generar(Request $request, Empresa $empresa, SaldoService $saldos)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfYear();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : now()->endOfYear();

        $excluirCierreAnio = $request->boolean('pre_cierre') ? (int) $hasta->year : null;

        $indice = $saldos->indice($empresa, $desde, $hasta, $excluirCierreAnio);

        // Cuentas "dormidas": saldo arrastrado, sin movimiento en el período.
        // Se agregan con su apertura por lado para que muestren su saldo.
        // Las de RESULTADO no se agregan (arrancan en cero cada ejercicio).
        $idsConMovimiento = $indice->pluck('cuenta.id')->all();
        $todasImputables = $empresa->cuentas()->where('imputable', true)->get();

        foreach ($todasImputables as $cuenta) {
            if (in_array($cuenta->id, $idsConMovimiento, true)) {
                continue;
            }
            if ($cuenta->clase === 'resultado') {
                continue;
            }
            $anterior = $saldos->saldoAnterior($cuenta, $desde, $excluirCierreAnio);
            $saldoAnteriorNeto = $saldos->saldoNeto($cuenta, $anterior['debe'], $anterior['haber']);
            if (bccomp($saldoAnteriorNeto, '0', 2) === 0) {
                continue; // sin saldo, no aporta al balance
            }
            $apertura = $saldos->aperturaPorLado($cuenta, $desde, $excluirCierreAnio);
            $indice->push((object) [
                'cuenta'        => $cuenta,
                'saldoAnterior' => $saldoAnteriorNeto,
                'debe'          => '0',
                'haber'         => '0',
                'aperturaDebe'  => $apertura['debe'],
                'aperturaHaber' => $apertura['haber'],
                'saldoFinal'    => $saldoAnteriorNeto,
                'esDeudora'     => $saldos->esDeudora($cuenta),
            ]);
        }

        $filas = $indice->map(function ($f) {
            $clase = $f->cuenta->clase;

            // ── Columnas SUMAS (Debe/Haber) ──
            if ($clase === 'resultado') {
                // Resultado: solo movimiento del año (sin apertura).
                $sumasDebe  = $f->debe;
                $sumasHaber = $f->haber;
            } else {
                // Balance: apertura por lado + movimiento del año.
                $sumasDebe  = bcadd($f->aperturaDebe  ?? '0', $f->debe,  2);
                $sumasHaber = bcadd($f->aperturaHaber ?? '0', $f->haber, 2);
            }

            // ── Columnas SALDOS / INVENTARIO / RESULTADO ──
            if ($clase === 'resultado') {
                $netoPeriodo = $f->esDeudora
                    ? bcsub($f->debe, $f->haber, 2)
                    : bcsub($f->haber, $f->debe, 2);
                $real = $f->esDeudora ? $netoPeriodo : bcmul($netoPeriodo, '-1', 2);
            } else {
                $real = $f->esDeudora ? $f->saldoFinal : bcmul($f->saldoFinal, '-1', 2);
            }

            $saldoDeudor   = bccomp($real, '0', 2) === 1  ? $real : '0';
            $saldoAcreedor = bccomp($real, '0', 2) === -1 ? bcmul($real, '-1', 2) : '0';

            $activoCol = $pasivoCol = $perdidaCol = $ganciaCol = '0';

            if ($clase === 'activo') {
                $activoCol = $saldoDeudor;
                $pasivoCol = $saldoAcreedor;
            } elseif (in_array($clase, ['pasivo', 'patrimonio'])) {
                $pasivoCol = $saldoAcreedor;
                $activoCol = $saldoDeudor;
            } elseif ($clase === 'resultado') {
                $perdidaCol = $saldoDeudor;
                $ganciaCol  = $saldoAcreedor;
            }

            return (object) array_merge((array) $f, [
                'sumasDebe' => $sumasDebe, 'sumasHaber' => $sumasHaber,
                'saldoDeudor' => $saldoDeudor, 'saldoAcreedor' => $saldoAcreedor,
                'activoCol' => $activoCol, 'pasivoCol' => $pasivoCol,
                'perdidaCol' => $perdidaCol, 'ganciaCol' => $ganciaCol,
            ]);
        });

        $porClase = $filas->groupBy(fn ($f) => $f->cuenta->clase);

        $totales = [
            'debe'     => $filas->reduce(fn ($a, $f) => bcadd($a, $f->sumasDebe, 2), '0'),
            'haber'    => $filas->reduce(fn ($a, $f) => bcadd($a, $f->sumasHaber, 2), '0'),
            'deudor'   => $filas->reduce(fn ($a, $f) => bcadd($a, $f->saldoDeudor, 2), '0'),
            'acreedor' => $filas->reduce(fn ($a, $f) => bcadd($a, $f->saldoAcreedor, 2), '0'),
            'activo'   => $filas->reduce(fn ($a, $f) => bcadd($a, $f->activoCol, 2), '0'),
            'pasivo'   => $filas->reduce(fn ($a, $f) => bcadd($a, $f->pasivoCol, 2), '0'),
            'perdida'  => $filas->reduce(fn ($a, $f) => bcadd($a, $f->perdidaCol, 2), '0'),
            'ganancia' => $filas->reduce(fn ($a, $f) => bcadd($a, $f->ganciaCol, 2), '0'),
        ];

        $resultado = bcsub($totales['ganancia'], $totales['perdida'], 2);
        $esUtilidad = bccomp($resultado, '0', 2) === 1;

        if ($esUtilidad) {
            $plugActivo = '0'; $plugPasivo = $resultado;
            $plugPerdida = $resultado; $plugGanancia = '0';
        } elseif (bccomp($resultado, '0', 2) === -1) {
            $abs = bcmul($resultado, '-1', 2);
            $plugActivo = $abs; $plugPasivo = '0';
            $plugPerdida = '0'; $plugGanancia = $abs;
        } else {
            $plugActivo = $plugPasivo = $plugPerdida = $plugGanancia = '0';
        }

        $resultadoEjercicio = [
            'activo' => $plugActivo, 'pasivo' => $plugPasivo,
            'perdida' => $plugPerdida, 'ganancia' => $plugGanancia,
        ];

        $totalesIguales = [
            'debe' => $totales['debe'], 'haber' => $totales['haber'],
            'deudor' => $totales['deudor'], 'acreedor' => $totales['acreedor'],
            'activo' => bcadd($totales['activo'], $plugActivo, 2),
            'pasivo' => bcadd($totales['pasivo'], $plugPasivo, 2),
            'perdida' => bcadd($totales['perdida'], $plugPerdida, 2),
            'ganancia' => bcadd($totales['ganancia'], $plugGanancia, 2),
        ];

        $folio = $request->query('folio');
        $empNombreImpresion = preg_replace('/\s*\(PRUEBA\)\s*$/i', '', $empresa->razon_social);

        $pdf = Pdf::loadView('pdf.balance8', compact(
            'empresa', 'porClase', 'totales', 'resultadoEjercicio', 'totalesIguales', 'desde', 'hasta', 'folio', 'empNombreImpresion'
        ))->setPaper('letter', 'landscape');

        $nombreArchivo = "balance-8-columnas-{$empresa->rut}-{$hasta->format('Y-m')}.pdf";

        return $pdf->stream($nombreArchivo);
    }
}
