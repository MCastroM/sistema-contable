<?php

namespace App\Services;

use App\Models\DocumentoVenta;
use App\Models\Empresa;
use App\Models\Periodo;
use Illuminate\Support\Facades\DB;

class CentralizacionVentasService
{
    public function __construct(
        private ComprobanteService $comprobantes,
    ) {}

    /**
     * Genera UN comprobante que resume el mes de ventas:
     *   DEBE  Deudores por Ventas / Clientes  = suma(total)
     *   HABER Cuenta de ingreso (por documento, agrupada) = suma(neto+exento)
     *   HABER IVA debito fiscal (si corresponde) = suma(iva)
     */
    public function centralizar(Empresa $empresa, Periodo $periodo, int $mes): \App\Models\Comprobante
    {
        if (! $empresa->cuenta_deudores_ventas_id) {
            throw new \RuntimeException(
                'Configura primero la cuenta de Deudores por Ventas / Clientes en los datos de la empresa.'
            );
        }

        $documentos = DocumentoVenta::where('empresa_id', $empresa->id)
            ->where('periodo_id', $periodo->id)
            ->where('mes', $mes)
            ->whereNull('comprobante_id')
            ->get();

        if ($documentos->isEmpty()) {
            throw new \RuntimeException('No hay documentos de venta pendientes de centralizar para ese mes.');
        }

        $sinCuenta = $documentos->whereNull('cuenta_ingreso_id');
        if ($sinCuenta->isNotEmpty()) {
            throw new \RuntimeException(
                "{$sinCuenta->count()} documento(s) no tienen cuenta de ingreso asignada. Asignalas antes de centralizar."
            );
        }

        $porCuenta = $documentos->groupBy('cuenta_ingreso_id');

        $lineas = [];
        $totalGeneral = $documentos->reduce(fn ($acc, $d) => bcadd($acc, (string) $d->total, 2), '0');
        $lineas[] = ['cuenta_id' => $empresa->cuenta_deudores_ventas_id, 'debe' => $totalGeneral, 'haber' => 0,
                     'glosa' => 'Centraliza libro de ventas'];

        foreach ($porCuenta as $cuentaId => $docs) {
            $base = $docs->reduce(fn ($acc, $d) => bcadd($acc, bcadd((string) $d->neto, (string) $d->exento, 2), 2), '0');
            if (bccomp($base, '0', 2) === 1) {
                $lineas[] = ['cuenta_id' => $cuentaId, 'debe' => 0, 'haber' => $base,
                             'glosa' => 'Centraliza libro de ventas'];
            }
        }

        $totalIva = $documentos->reduce(fn ($acc, $d) => bcadd($acc, (string) $d->iva, 2), '0');
        if (bccomp($totalIva, '0', 2) === 1) {
            if (! $empresa->cuenta_iva_debito_id) {
                throw new \RuntimeException('Hay IVA en las ventas pero falta configurar la cuenta de IVA débito fiscal.');
            }
            $lineas[] = ['cuenta_id' => $empresa->cuenta_iva_debito_id, 'debe' => 0, 'haber' => $totalIva,
                         'glosa' => 'IVA débito fiscal del mes'];
        }

        return DB::transaction(function () use ($empresa, $periodo, $mes, $lineas, $documentos) {
            $fecha = \Carbon\Carbon::create($periodo->anio, $mes, 1)->endOfMonth();

            $comprobante = $this->comprobantes->crearBorrador(
                $empresa, 'T', $fecha,
                sprintf('Centralización Libro de Ventas %02d/%d', $mes, $periodo->anio),
                $lineas,
            );

            $this->comprobantes->aprobar($comprobante);

            DocumentoVenta::whereIn('id', $documentos->pluck('id'))
                ->update(['comprobante_id' => $comprobante->id]);

            return $comprobante;
        });
    }
}
