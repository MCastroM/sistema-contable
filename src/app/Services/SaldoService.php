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
     */
    public function esDeudora(Cuenta $cuenta): bool
    {
        return in_array(substr($cuenta->codigo, 0, 1), ['1', '5'], true);
    }

    /**
     * Saldo de una cuenta ANTES de una fecha (arrastre inicial).
     * Solo considera comprobantes APROBADOS.
     *
     * $excluirCierreAnio: si se indica un año, ignora el asiento
     * "CIERRE DEL EJERCICIO {año}" de ESE año (balance pre-cierre).
     */
    public function saldoAnterior(Cuenta $cuenta, Carbon $desde, ?int $excluirCierreAnio = null): array
    {
        $sumas = Movimiento::query()
            ->where('cuenta_id', $cuenta->id)
            ->whereHas('comprobante', function ($q) use ($desde, $excluirCierreAnio) {
                $q->where('estado', Comprobante::APROBADO)
                  ->where('fecha', '<', $desde->toDateString());
                if ($excluirCierreAnio !== null) {
                    $q->where('glosa', 'not like', "CIERRE DEL EJERCICIO {$excluirCierreAnio}%");
                }
            })
            ->selectRaw('COALESCE(SUM(debe),0) as debe, COALESCE(SUM(haber),0) as haber')
            ->first();

        return [
            'debe'  => (string) ($sumas->debe ?? '0'),
            'haber' => (string) ($sumas->haber ?? '0'),
        ];
    }

    /**
     * Saldo de apertura NETO repartido por lado, según naturaleza.
     * Devuelve el saldo anterior como un débito o crédito inicial:
     *  - cuenta deudora  con saldo deudor  -> ['debe' => saldo, 'haber' => 0]
     *  - cuenta acreedora con saldo acreedor-> ['debe' => 0, 'haber' => saldo]
     *  - si el saldo resulta de naturaleza contraria, se ubica en el
     *    lado que refleje el signo real (para no romper el cuadre).
     *
     * Se usa para incorporar la apertura a las columnas SUMAS del balance.
     */
    public function aperturaPorLado(Cuenta $cuenta, Carbon $desde, ?int $excluirCierreAnio = null): array
    {
        $ant = $this->saldoAnterior($cuenta, $desde, $excluirCierreAnio);
        // saldo neto en términos de debe-haber (positivo = neto deudor)
        $netoDebeHaber = bcsub($ant['debe'], $ant['haber'], 2);

        if (bccomp($netoDebeHaber, '0', 2) >= 0) {
            // neto deudor (o cero): va al DEBE
            return ['debe' => $netoDebeHaber, 'haber' => '0'];
        }
        // neto acreedor: va al HABER (valor absoluto)
        return ['debe' => '0', 'haber' => bcmul($netoDebeHaber, '-1', 2)];
    }

    /**
     * Saldo neto con signo según naturaleza:
     * deudora  -> debe - haber
     * acreedora-> haber - debe
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
     *
     * Incluye:
     *  - debe / haber   : movimiento DEL PERÍODO (desde..hasta)
     *  - aperturaDebe / aperturaHaber : saldo de apertura neto por lado
     *  - saldoAnterior  : saldo neto (naturaleza) antes de 'desde'
     *  - saldoFinal     : saldo neto (naturaleza) acumulado hasta 'hasta'
     *
     * $excluirCierreAnio: ignora "CIERRE DEL EJERCICIO {año}" (pre-cierre).
     */
    public function indice(Empresa $empresa, Carbon $desde, Carbon $hasta, ?int $excluirCierreAnio = null): Collection
    {
        $totales = Movimiento::query()
            ->whereHas('comprobante', function ($q) use ($empresa, $desde, $hasta, $excluirCierreAnio) {
                $q->where('empresa_id', $empresa->id)
                  ->where('estado', Comprobante::APROBADO)
                  ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
                if ($excluirCierreAnio !== null) {
                    $q->where('glosa', 'not like', "CIERRE DEL EJERCICIO {$excluirCierreAnio}%");
                }
            })
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

        return $cuentas->map(function (Cuenta $cuenta) use ($totales, $desde, $excluirCierreAnio) {
            $anterior = $this->saldoAnterior($cuenta, $desde, $excluirCierreAnio);
            $periodo  = $totales->get($cuenta->id);

            $debePeriodo  = (string) $periodo->debe;
            $haberPeriodo = (string) $periodo->haber;

            $debeAcum  = bcadd($anterior['debe'],  $debePeriodo,  2);
            $haberAcum = bcadd($anterior['haber'], $haberPeriodo, 2);

            $apertura = $this->aperturaPorLado($cuenta, $desde, $excluirCierreAnio);

            return (object) [
                'cuenta'        => $cuenta,
                'saldoAnterior' => $this->saldoNeto($cuenta, $anterior['debe'], $anterior['haber']),
                'debe'          => $debePeriodo,
                'haber'         => $haberPeriodo,
                'aperturaDebe'  => $apertura['debe'],
                'aperturaHaber' => $apertura['haber'],
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
