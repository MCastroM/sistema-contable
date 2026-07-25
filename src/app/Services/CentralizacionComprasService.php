<?php

namespace App\Services;

use App\Models\DocumentoCompra;
use App\Models\Empresa;
use App\Models\Periodo;
use Illuminate\Support\Facades\DB;

class CentralizacionComprasService
{
    public function __construct(
        private ComprobanteService $comprobantes,
    ) {}

    /**
     * Genera UN comprobante tipo Traspaso que resume todo el libro de
     * compras del mes: agrupa los documentos por cuenta de gasto,
     * imputa el IVA crédito fiscal a su cuenta, y acredita el total
     * a Proveedores. Es el mismo patrón "CENTRALIZA LIBRO DE COMPRAS"
     * que viste en el libro diario de ABSAL.
     */
    public function centralizar(Empresa $empresa, Periodo $periodo, int $mes): \App\Models\Comprobante
    {
        if (! $empresa->cuenta_proveedores_id || ! $empresa->cuenta_iva_credito_id) {
            throw new \RuntimeException(
                'Configura primero las cuentas de Proveedores e IVA crédito fiscal en los datos de la empresa.'
            );
        }

        $documentos = DocumentoCompra::where('empresa_id', $empresa->id)
            ->where('periodo_id', $periodo->id)
            ->where('mes', $mes)
            ->whereNull('comprobante_id')
            ->get();

        if ($documentos->isEmpty()) {
            throw new \RuntimeException('No hay documentos de compra pendientes de centralizar para ese mes.');
        }

        $sinCuenta = $documentos->whereNull('cuenta_gasto_id');
        if ($sinCuenta->isNotEmpty()) {
            throw new \RuntimeException(
                "{$sinCuenta->count()} documento(s) no tienen cuenta de gasto asignada. Asígnalas antes de centralizar."
            );
        }

        // Agrupar por cuenta de gasto: cada una recibe (neto + exento) al debe
        $porCuenta = $documentos->groupBy('cuenta_gasto_id');

        $lineas = [];
        foreach ($porCuenta as $cuentaId => $docs) {
            $base = $docs->reduce(fn ($acc, $d) => bcadd($acc, bcadd((string) $d->neto, (string) $d->exento, 2), 2), '0');
            if (bccomp($base, '0', 2) === 1) {
                $lineas[] = ['cuenta_id' => $cuentaId, 'debe' => $base, 'haber' => 0,
                             'glosa' => 'Centraliza libro de compras'];
            }
        }

        $totalIva = $documentos->reduce(fn ($acc, $d) => bcadd($acc, (string) $d->iva, 2), '0');
        if (bccomp($totalIva, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $empresa->cuenta_iva_credito_id, 'debe' => $totalIva, 'haber' => 0,
                         'glosa' => 'IVA crédito fiscal del mes'];
        }

        $totalGeneral = $documentos->reduce(fn ($acc, $d) => bcadd($acc, (string) $d->total, 2), '0');
        $lineas[] = ['cuenta_id' => $empresa->cuenta_proveedores_id, 'debe' => 0, 'haber' => $totalGeneral,
                     'glosa' => 'Centraliza libro de compras'];

        return DB::transaction(function () use ($empresa, $periodo, $mes, $lineas, $documentos) {
            $fecha = \Carbon\Carbon::create($periodo->anio, $mes, 1)->endOfMonth();

            $comprobante = $this->comprobantes->crearBorrador(
                $empresa, 'T', $fecha,
                sprintf('Centralización Libro de Compras %02d/%d', $mes, $periodo->anio),
                $lineas,
            );

            $this->comprobantes->aprobar($comprobante);

            // Marcar los documentos como centralizados
            DocumentoCompra::whereIn('id', $documentos->pluck('id'))
                ->update(['comprobante_id' => $comprobante->id]);

            return $comprobante;
        });
    }
}
