<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\Empresa;
use App\Models\Movimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CierreEjercicioService
{
    public function __construct(
        private ComprobanteService $comprobantes,
    ) {}

    /**
     * Genera el asiento de CIERRE DEL EJERCICIO para el año indicado:
     * deja en cero todas las cuentas de Resultado (Ventas, Sueldos,
     * Gastos, etc.) y traspasa la utilidad o perdida neta a
     * "Resultados acumulados" (Patrimonio).
     *
     * Sin este asiento, las cuentas de Resultado seguirian arrastrando
     * su saldo de un año a otro indefinidamente, y el Balance de 8
     * columnas del año siguiente no cuadraria (Deudor != Acreedor)
     * si se intenta mostrar solo la utilidad del periodo en curso.
     */
    public function cerrar(Empresa $empresa, int $anio): Comprobante
    {
        $periodo = $empresa->periodo($anio);
        if (! $periodo) {
            throw new \RuntimeException("No existe el período {$anio} para esta empresa.");
        }

        $fechaCierre = Carbon::create($anio, 12, 31)->endOfDay();

        $yaExiste = Comprobante::where('empresa_id', $empresa->id)
            ->where('periodo_id', $periodo->id)
            ->where('glosa', 'like', 'CIERRE DEL EJERCICIO%')
            ->exists();
        if ($yaExiste) {
            throw new \RuntimeException("Ya existe un cierre de ejercicio para el año {$anio}.");
        }

        $cuentasResultado = $empresa->cuentas()
            ->where('clase', 'resultado')
            ->where('imputable', true)
            ->get();

        $lineas = [];
        $totalPerdida = '0';
        $totalGanancia = '0';

        foreach ($cuentasResultado as $cuenta) {
            // Saldo acumulado de la cuenta hasta la fecha de cierre
            // (incluye TODO el historico, no solo el año que se cierra --
            // asi el cierre tambien "arrastra y limpia" cualquier residuo
            // de años anteriores que nunca se cerraron).
            $suma = Movimiento::whereHas('comprobante', fn ($q) => $q
                    ->where('empresa_id', $empresa->id)
                    ->where('estado', 'aprobado')
                    ->where('fecha', '<=', $fechaCierre->toDateString()))
                ->where('cuenta_id', $cuenta->id)
                ->selectRaw('SUM(debe) as d, SUM(haber) as h')
                ->first();

            $debe = (string) ($suma->d ?? '0');
            $haber = (string) ($suma->h ?? '0');
            $esDeudora = in_array(substr($cuenta->codigo, 0, 1), ['1', '5'], true);
            $neto = $esDeudora ? bcsub($debe, $haber, 2) : bcsub($haber, $debe, 2);

            if (bccomp($neto, '0', 2) === 0) {
                continue; // cuenta ya en cero, nada que cerrar
            }

            if ($esDeudora) { // cuenta de GASTO
                if (bccomp($neto, '0', 2) === 1) {
                    $lineas[] = ['cuenta_id' => $cuenta->id, 'debe' => 0, 'haber' => $neto,
                                 'glosa' => 'Cierre del ejercicio ' . $anio];
                    $totalPerdida = bcadd($totalPerdida, $neto, 2);
                } else {
                    $abs = bcmul($neto, '-1', 2);
                    $lineas[] = ['cuenta_id' => $cuenta->id, 'debe' => $abs, 'haber' => 0,
                                 'glosa' => 'Cierre del ejercicio ' . $anio];
                    $totalGanancia = bcadd($totalGanancia, $abs, 2);
                }
            } else { // cuenta de INGRESO
                if (bccomp($neto, '0', 2) === 1) {
                    $lineas[] = ['cuenta_id' => $cuenta->id, 'debe' => $neto, 'haber' => 0,
                                 'glosa' => 'Cierre del ejercicio ' . $anio];
                    $totalGanancia = bcadd($totalGanancia, $neto, 2);
                } else {
                    $abs = bcmul($neto, '-1', 2);
                    $lineas[] = ['cuenta_id' => $cuenta->id, 'debe' => 0, 'haber' => $abs,
                                 'glosa' => 'Cierre del ejercicio ' . $anio];
                    $totalPerdida = bcadd($totalPerdida, $abs, 2);
                }
            }
        }

        if (empty($lineas)) {
            throw new \RuntimeException('No hay cuentas de Resultado con saldo para cerrar.');
        }

        $resultadoNeto = bcsub($totalGanancia, $totalPerdida, 2); // positivo = utilidad

        $cuentaResultadosAcum = $empresa->cuentas()->where('nombre', 'Resultados acumulados')->first();
        if (! $cuentaResultadosAcum) {
            throw new \RuntimeException('No existe la cuenta "Resultados acumulados" en el plan de cuentas.');
        }

        if (bccomp($resultadoNeto, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $cuentaResultadosAcum->id, 'debe' => 0, 'haber' => $resultadoNeto,
                         'glosa' => 'Utilidad del ejercicio ' . $anio];
        } elseif (bccomp($resultadoNeto, '0', 2) === -1) {
            $abs = bcmul($resultadoNeto, '-1', 2);
            $lineas[] = ['cuenta_id' => $cuentaResultadosAcum->id, 'debe' => $abs, 'haber' => 0,
                         'glosa' => 'Pérdida del ejercicio ' . $anio];
        }

        return DB::transaction(function () use ($empresa, $fechaCierre, $anio, $lineas) {
            $comprobante = $this->comprobantes->crearBorrador(
                $empresa, 'T', $fechaCierre, "CIERRE DEL EJERCICIO {$anio}", $lineas,
            );
            $this->comprobantes->aprobar($comprobante);

            return $comprobante;
        });
    }
}
