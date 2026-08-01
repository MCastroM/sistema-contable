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
     *
     * Parámetro opcional ?pre_cierre=1 : balance ANTES del cierre del año
     * en curso. Excluye ÚNICAMENTE el asiento "CIERRE DEL EJERCICIO {año}"
     * de ESE año (el año de la fecha 'hasta'), de modo que:
     *   - Las cuentas de Resultado muestran su saldo del ejercicio.
     *   - El patrimonio NO incluye todavía la utilidad del propio año
     *     (esa aparece en la línea "Resultado del Ejercicio", y se
     *     incorpora a Resultados Acumulados recién en la apertura del
     *     año siguiente).
     * Los cierres de años ANTERIORES sí se cuentan: su utilidad ya es
     * parte del patrimonio arrastrado.
     */
    public function generar(Request $request, Empresa $empresa, SaldoService $saldos)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfYear();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : now()->endOfYear();

        // Año cuyo asiento de cierre se excluye (solo si se pide pre-cierre).
        $excluirCierreAnio = $request->boolean('pre_cierre') ? (int) $hasta->year : null;

        // Mismo índice que usa el Libro Mayor: saldo anterior + sumas del
        // período + saldo final, ya con signo ajustado por naturaleza.
        $indice = $saldos->indice($empresa, $desde, $hasta, $excluirCierreAnio);

        // IMPORTANTE: indice() SOLO devuelve cuentas con movimiento en el
        // período (correcto para el Libro Mayor). Un Balance, en cambio,
        // debe mostrar TODAS las cuentas con saldo distinto de cero, tengan
        // o no actividad este año -- si no, una cuenta con saldo arrastrado
        // de un año anterior "desaparece" del balance sin dejar rastro,
        // rompiendo la igualdad Deudor=Acreedor. Agregamos esas cuentas
        // "dormidas" (saldo arrastrado, sin movimiento este período).
        //
        // NOTA: las cuentas de RESULTADO no se agregan como "dormidas":
        // arrancan cada ejercicio en cero (gracias al asiento de cierre del
        // año anterior) y solo aparecen si tienen movimiento en el período.
        $idsConMovimiento = $indice->pluck('cuenta.id')->all();
        $todasImputables = $empresa->cuentas()->where('imputable', true)->get();

        foreach ($todasImputables as $cuenta) {
            if (in_array($cuenta->id, $idsConMovimiento, true)) {
                continue;
            }
            // Las cuentas de resultado NO arrastran saldo entre ejercicios.
            if ($cuenta->clase === 'resultado') {
                continue;
            }
            $anterior = $saldos->saldoAnterior($cuenta, $desde, $excluirCierreAnio);
            $saldoAnteriorNeto = $saldos->saldoNeto($cuenta, $anterior['debe'], $anterior['haber']);
            if (bccomp($saldoAnteriorNeto, '0', 2) === 0) {
                continue; // sin saldo, no aporta nada al balance
            }
            $indice->push((object) [
                'cuenta'        => $cuenta,
                'saldoAnterior' => $saldoAnteriorNeto,
                'debe'          => '0',
                'haber'         => '0',
                'saldoFinal'    => $saldoAnteriorNeto,
                'esDeudora'     => $saldos->esDeudora($cuenta),
            ]);
        }

        $filas = $indice->map(function ($f) {
            $clase = $f->cuenta->clase;

            // ── Criterio de saldo según tipo de cuenta ──
            // RESULTADO (Ventas, Sueldos, gastos...): solo el MOVIMIENTO DEL
            //   PERÍODO. No arrastran saldo entre ejercicios porque el asiento
            //   de CIERRE del año anterior las dejó en cero. Mostrar su
            //   acumulado histórico inflaría el resultado del ejercicio.
            // BALANCE (activo/pasivo/patrimonio): saldo FINAL acumulado
            //   (saldo anterior + período), porque su saldo sí se arrastra.
            if ($clase === 'resultado') {
                $netoPeriodo = $f->esDeudora
                    ? bcsub($f->debe, $f->haber, 2)   // gasto: debe - haber
                    : bcsub($f->haber, $f->debe, 2);  // ingreso: haber - debe
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
        $empNombreImpresion = preg_replace('/\s*\(PRUEBA\)\s*$/i', '', $empresa->razon_social);

        $pdf = Pdf::loadView('pdf.balance8', compact(
            'empresa', 'porClase', 'totales', 'resultadoEjercicio', 'totalesIguales', 'desde', 'hasta', 'folio', 'empNombreImpresion'
        ))->setPaper('letter', 'landscape');

        $nombreArchivo = "balance-8-columnas-{$empresa->rut}-{$hasta->format('Y-m')}.pdf";

        return $pdf->stream($nombreArchivo); // stream = se abre en el navegador; download() para forzar descarga
    }
}
