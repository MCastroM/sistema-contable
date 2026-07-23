<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\Cuenta;
use App\Models\Empresa;
use App\Models\Movimiento;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SaldoService
{
    /**
     * Naturaleza de cada clase de cuenta: determina si el saldo
     * es deudor (debe - haber) o acreedor (haber - debe).
     *
     * Activo y pérdidas -> deudoras
     * Pasivo, patrimonio y ganancias -> acreedoras
     *
     * Nota: en nuestro plan, ganancias (4) y pérdidas (5) comparten
     * la clase 'resultado', así que la naturaleza se deduce del
     * primer dígito del código.
     */
    public function esDeudora(Cuenta $cuenta): bool
    {
        return in_array(substr($cuenta->codigo, 0, 1), ['1', '5'], true);
    }

    /**
     * Saldo de una cuenta ANTES de una fecha (arrastre inicial).
     * Solo considera comprobantes APROBADOS.
     */
    public function saldoAnterior(Cuenta $cuenta, Carbon $desde): array
    {
        $sumas = Movimiento::query()
            ->where('cuenta_id', $cuenta->id)
            ->whereHas('comprobante', fn ($q) => $q
                ->where('estado', Comprobante::APROBADO)
                ->where('fecha', '<', $desde->toDateString()))
            ->selectRaw('COALESCE(SUM(debe),0) as debe, COALESCE(SUM(haber),0) as haber')
            ->first();

        return [
            'debe'  => (string) ($sumas->debe ?? '0'),
            'haber' => (string) ($sumas->haber ?? '0'),
        ];
    }

    /**
     * Saldo neto con signo según naturaleza:
     * deudora  -> debe - haber
     * acreedora-> haber - debe
     * Un saldo negativo indica naturaleza contraria a la esperada.
     */
    public function saldoNeto(Cuenta $cuenta, string $debe, string $haber): string
    {
        return $this->esDeudora($cuenta)
            ? bcsub($debe, $haber, 2)
            : bcsub($haber, $debe, 2);
    }

    /**
     * Índice del mayor: todas las cuentas imputables CON movimiento
     * en el rango, con sus totales y saldos.
     */
    public function indice(Empresa $empresa, Carbon $desde, Carbon $hasta): Collection
    {
        // Totales del período por cuenta, en una sola consulta agregada
        $totales = Movimiento::query()
            ->whereHas('comprobante', fn ($q) => $q
                ->where('empresa_id', $empresa->id)
                ->where('estado', Comprobante::APROBADO)
                ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]))
            ->selectRaw('cuenta_id, SUM(debe) as debe, SUM(haber) as haber')
            ->groupBy('cuenta_id')
            ->get()
            ->keyBy('cuenta_id');

        if ($totales->isEmpty()) {
            return collect();
        }

        $cuentas = Cuenta::whereIn('id', $totales->keys())
            ->orderBy('codigo')
            ->get();

        return $cuentas->map(function (Cuenta $cuenta) use ($totales, $desde) {
            $anterior = $this->saldoAnterior($cuenta, $desde);
            $periodo  = $totales->get($cuenta->id);

            $debePeriodo  = (string) $periodo->debe;
            $haberPeriodo = (string) $periodo->haber;

            $debeAcum  = bcadd($anterior['debe'],  $debePeriodo,  2);
            $haberAcum = bcadd($anterior['haber'], $haberPeriodo, 2);

            return (object) [
                'cuenta'        => $cuenta,
                'saldoAnterior' => $this->saldoNeto($cuenta, $anterior['debe'], $anterior['haber']),
                'debe'          => $debePeriodo,
                'haber'         => $haberPeriodo,
                'saldoFinal'    => $this->saldoNeto($cuenta, $debeAcum, $haberAcum),
                'esDeudora'     => $this->esDeudora($cuenta),
            ];
        });
    }

    /**
     * Detalle de una cuenta: sus movimientos en el rango,
     * con saldo corriente acumulado línea a línea.
     */
    public function detalle(Cuenta $cuenta, Carbon $desde, Carbon $hasta): array
    {
        $anterior = $this->saldoAnterior($cuenta, $desde);
        $saldoCorriente = $this->saldoNeto($cuenta, $anterior['debe'], $anterior['haber']);

        $movimientos = Movimiento::query()
            ->where('cuenta_id', $cuenta->id)
            ->whereHas('comprobante', fn ($q) => $q
                ->where('estado', Comprobante::APROBADO)
                ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]))
            ->with(['comprobante:id,tipo,numero,fecha,glosa,periodo_id',
                    'comprobante.periodo:id,anio',
                    'centroCosto:id,codigo'])
            ->join('comprobantes', 'movimientos.comprobante_id', '=', 'comprobantes.id')
            ->orderBy('comprobantes.fecha')
            ->orderBy('comprobantes.id')
            ->orderBy('movimientos.id')
            ->select('movimientos.*')
            ->get();

        $lineas = [];
        $totalDebe  = '0';
        $totalHaber = '0';

        foreach ($movimientos as $m) {
            $delta = $this->esDeudora($cuenta)
                ? bcsub((string) $m->debe, (string) $m->haber, 2)
                : bcsub((string) $m->haber, (string) $m->debe, 2);

            $saldoCorriente = bcadd($saldoCorriente, $delta, 2);
            $totalDebe  = bcadd($totalDebe,  (string) $m->debe,  2);
            $totalHaber = bcadd($totalHaber, (string) $m->haber, 2);

            $lineas[] = (object) [
                'movimiento' => $m,
                'saldo'      => $saldoCorriente,
            ];
        }

        return [
            'saldoAnterior' => $this->saldoNeto($cuenta, $anterior['debe'], $anterior['haber']),
            'lineas'        => $lineas,
            'totalDebe'     => $totalDebe,
            'totalHaber'    => $totalHaber,
            'saldoFinal'    => $saldoCorriente,
            'esDeudora'     => $this->esDeudora($cuenta),
        ];
    }
}
