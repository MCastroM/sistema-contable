<?php

namespace App\Services;

use App\Models\BoletaHonorario;
use App\Models\Empresa;
use App\Models\Periodo;
use Illuminate\Support\Facades\DB;

class CentralizacionHonorariosService
{
    public function __construct(
        private ComprobanteService $comprobantes,
    ) {}

    /**
     * Genera UN comprobante de traspaso: cada cuenta de gasto recibe
     * el bruto de sus boletas al debe; Retención de Honorarios se
     * acredita por el total retenido; Honorarios por Pagar se acredita
     * por el líquido (lo que realmente se les pagará a los prestadores).
     */
    public function centralizar(Empresa $empresa, Periodo $periodo, int $mes): \App\Models\Comprobante
    {
        if (! $empresa->cuenta_honorarios_pagar_id || ! $empresa->cuenta_retencion_honorarios_id) {
            throw new \RuntimeException(
                'Configura primero las cuentas de Honorarios por Pagar y Retención de Honorarios en la empresa.'
            );
        }

        $boletas = BoletaHonorario::where('empresa_id', $empresa->id)
            ->where('periodo_id', $periodo->id)
            ->where('mes', $mes)
            ->whereNull('comprobante_id')
            ->get();

        if ($boletas->isEmpty()) {
            throw new \RuntimeException('No hay boletas de honorarios pendientes de centralizar para ese mes.');
        }

        $sinCuenta = $boletas->whereNull('cuenta_gasto_id');
        if ($sinCuenta->isNotEmpty()) {
            throw new \RuntimeException(
                "{$sinCuenta->count()} boleta(s) no tienen cuenta de gasto asignada. Asígnalas antes de centralizar."
            );
        }

        $porCuenta = $boletas->groupBy('cuenta_gasto_id');

        $lineas = [];
        foreach ($porCuenta as $cuentaId => $docs) {
            $bruto = $docs->reduce(fn ($acc, $d) => bcadd($acc, (string) $d->brutos, 2), '0');
            $lineas[] = ['cuenta_id' => $cuentaId, 'debe' => $bruto, 'haber' => 0,
                         'glosa' => 'Centraliza libro de honorarios'];
        }

        $totalRetencion = $boletas->reduce(fn ($acc, $d) => bcadd($acc, (string) $d->retencion, 2), '0');
        if (bccomp($totalRetencion, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $empresa->cuenta_retencion_honorarios_id, 'debe' => 0, 'haber' => $totalRetencion,
                         'glosa' => 'Retención honorarios del mes'];
        }

        $totalLiquido = $boletas->reduce(fn ($acc, $d) => bcadd($acc, (string) $d->total, 2), '0');
        $lineas[] = ['cuenta_id' => $empresa->cuenta_honorarios_pagar_id, 'debe' => 0, 'haber' => $totalLiquido,
                     'glosa' => 'Centraliza libro de honorarios'];

        return DB::transaction(function () use ($empresa, $periodo, $mes, $lineas, $boletas) {
            $fecha = \Carbon\Carbon::create($periodo->anio, $mes, 1)->endOfMonth();

            $comprobante = $this->comprobantes->crearBorrador(
                $empresa, 'T', $fecha,
                sprintf('Centralización Libro de Honorarios %02d/%d', $mes, $periodo->anio),
                $lineas,
            );

            $this->comprobantes->aprobar($comprobante);

            BoletaHonorario::whereIn('id', $boletas->pluck('id'))
                ->update(['comprobante_id' => $comprobante->id]);

            return $comprobante;
        });
    }
}
