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
     * Balance Tributario de 8 columnas en PDF, leyendo EN VIVO desde
     * la base de datos (SaldoService), no desde ningún archivo externo.
     * Cualquier corrección hecha en el sistema se refleja automáticamente
     * la próxima vez que se genera este PDF.
     */
    public function generar(Request $request, Empresa $empresa, SaldoService $saldos)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfYear();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : now()->endOfYear();

        // Mismo índice que usa el Libro Mayor: saldo anterior + sumas del
        // período + saldo final, ya con signo ajustado por naturaleza.
        $indice = $saldos->indice($empresa, $desde, $hasta);

        $filas = $indice->map(function ($f) {
            // Signo REAL (debe-haber acumulado), no el ajustado por naturaleza,
            // igual que en el Balance de Comprobación web.
            $real = $f->esDeudora ? $f->saldoFinal : bcmul($f->saldoFinal, '-1', 2);
            $saldoDeudor   = bccomp($real, '0', 2) === 1  ? $real : '0';
            $saldoAcreedor = bccomp($real, '0', 2) === -1 ? bcmul($real, '-1', 2) : '0';

            // Columnas 5-8: Inventario (Activo/Pasivo) y Resultado (Pérdida/Ganancia)
            $clase = $f->cuenta->clase;
            $activoCol = $pasivoCol = $perdidaCol = $ganciaCol = '0';

            if ($clase === 'activo') {
                $activoCol = $saldoDeudor;
                $pasivoCol = $saldoAcreedor; // contrario, si lo hubiera
            } elseif (in_array($clase, ['pasivo', 'patrimonio'])) {
                $pasivoCol = $saldoAcreedor;
                $activoCol = $saldoDeudor; // contrario, si lo hubiera
            } elseif ($clase === 'resultado') {
                $perdidaCol = $saldoDeudor;
                $ganciaCol  = $saldoAcreedor;
            }

            return (object) array_merge((array) $f, [
                'saldoDeudor' => $saldoDeudor, 'saldoAcreedor' => $saldoAcreedor,
                'activoCol' => $activoCol, 'pasivoCol' => $pasivoCol,
                'perdidaCol' => $perdidaCol, 'ganciaCol' => $ganciaCol,
            ]);
        });

        $porClase = $filas->groupBy(fn ($f) => $f->cuenta->clase);

        // Totales generales de las 8 columnas (deben cuadrar de a pares)
        $totales = [
            'debe'     => $filas->reduce(fn ($a, $f) => bcadd($a, $f->debe, 2), '0'),
            'haber'    => $filas->reduce(fn ($a, $f) => bcadd($a, $f->haber, 2), '0'),
            'deudor'   => $filas->reduce(fn ($a, $f) => bcadd($a, $f->saldoDeudor, 2), '0'),
            'acreedor' => $filas->reduce(fn ($a, $f) => bcadd($a, $f->saldoAcreedor, 2), '0'),
            'activo'   => $filas->reduce(fn ($a, $f) => bcadd($a, $f->activoCol, 2), '0'),
            'pasivo'   => $filas->reduce(fn ($a, $f) => bcadd($a, $f->pasivoCol, 2), '0'),
            'perdida'  => $filas->reduce(fn ($a, $f) => bcadd($a, $f->perdidaCol, 2), '0'),
            'ganancia' => $filas->reduce(fn ($a, $f) => bcadd($a, $f->ganciaCol, 2), '0'),
        ];

        // ── Resultado del Ejercicio: la utilidad (o perdida) del periodo
        //    se "inserta" en Activo/Pasivo Y en Perdida/Ganancia, para
        //    demostrar que el sistema cuadra en su conjunto (no solo
        //    columna por columna). Convencion clasica del balance de
        //    8 columnas chileno. ──
        $resultado = bcsub($totales['ganancia'], $totales['perdida'], 2);
        $esUtilidad = bccomp($resultado, '0', 2) === 1;

        if ($esUtilidad) {
            // Utilidad: se suma al Pasivo (aumenta patrimonio) y a la
            // Perdida (para igualar con Ganancia).
            $plugActivo = '0'; $plugPasivo = $resultado;
            $plugPerdida = $resultado; $plugGanancia = '0';
        } elseif (bccomp($resultado, '0', 2) === -1) {
            // Perdida neta: se suma al Activo y a la Ganancia.
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

        $folio = $request->query('folio'); // folio manual mientras no existe el ensamblado completo (Paquete C)

        $pdf = Pdf::loadView('pdf.balance8', compact(
            'empresa', 'porClase', 'totales', 'resultadoEjercicio', 'totalesIguales', 'desde', 'hasta', 'folio'
        ))->setPaper('letter', 'landscape');

        $nombreArchivo = "balance-8-columnas-{$empresa->rut}-{$hasta->format('Y-m')}.pdf";

        return $pdf->stream($nombreArchivo); // stream = se abre en el navegador; download() para forzar descarga
    }
}
