<?php

namespace App\Services;

use App\Models\Empresa;
use Carbon\Carbon;
use App\Models\DocumentoCompra;
use App\Models\BoletaHonorario;
use App\Models\RemuneracionTrabajador;
use App\Models\DocumentoVenta;

class PdfContableService
{
    public function __construct(
        private SaldoService $saldos,
    ) {}

    /**
     * Arma el listado COMPLETO del Mayor (todas las cuentas, una tras otra,
     * con su encabezado, movimientos y subtotal) tal como se imprime en
     * papel. Reutiliza SaldoService::detalle() cuenta por cuenta — la
     * misma lógica que ya validamos en el Libro Mayor web.
     */
    public function mayorCompleto(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $indice = $this->saldos->indice($empresa, $desde, $hasta);
        $cuentas = [];
        $totalDebe = '0';
        $totalHaber = '0';

        foreach ($indice as $fila) {
            $detalle = $this->saldos->detalle($fila->cuenta, $desde, $hasta);

            $cuentas[] = [
                'cuenta' => $fila->cuenta,
                'saldoAnterior' => $detalle['saldoAnterior'],
                'lineas' => $detalle['lineas'],
                'totalDebe' => $detalle['totalDebe'],
                'totalHaber' => $detalle['totalHaber'],
                'saldoFinal' => $detalle['saldoFinal'],
            ];

            $totalDebe = bcadd($totalDebe, $detalle['totalDebe'], 2);
            $totalHaber = bcadd($totalHaber, $detalle['totalHaber'], 2);
        }

        return [
            'cuentas' => $cuentas,
            'totalDebe' => $totalDebe,
            'totalHaber' => $totalHaber,
        ];
    }

    /**
     * Arma el listado completo del Diario: comprobantes aprobados en
     * orden cronológico, con sus líneas y el subtotal de cada asiento
     * (igual al formato que vimos en tu libro real ya impreso).
     */
    public function diarioCompleto(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $comprobantes = $empresa->comprobantes()
            ->where('estado', 'aprobado')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->with(['movimientos.cuenta:id,codigo,nombre', 'movimientos.centroCosto:id,codigo'])
            ->orderBy('fecha')->orderBy('id')
            ->get();

        $totalDebe = '0';
        $totalHaber = '0';
        foreach ($comprobantes as $c) {
            $totalDebe = bcadd($totalDebe, $c->totalDebe(), 2);
            $totalHaber = bcadd($totalHaber, $c->totalHaber(), 2);
        }

        return [
            'comprobantes' => $comprobantes,
            'totalDebe' => $totalDebe,
            'totalHaber' => $totalHaber,
        ];
    }

    /**
     * Version "aplanada" del mayor: lista simple de filas (separador de
     * cuenta, saldo anterior, movimientos, subtotal), lista para dividirse
     * en bloques de N filas con salto de pagina EXPLICITO. Evitamos
     * depender del corte automatico de tablas largas de DomPDF, que no
     * dispara el encabezado dinamico de forma confiable.
     */
    public function mayorFilasPlanas(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $mayor = $this->mayorCompleto($empresa, $desde, $hasta);
        $filas = [];

        foreach ($mayor['cuentas'] as $c) {
            $filas[] = ['tipo' => 'separador', 'cuenta' => $c['cuenta']];
            $filas[] = ['tipo' => 'saldo_anterior', 'cuenta' => $c['cuenta'], 'saldo' => $c['saldoAnterior']];
            foreach ($c['lineas'] as $l) {
                $filas[] = ['tipo' => 'movimiento', 'cuenta' => $c['cuenta'], 'linea' => $l];
            }
            $filas[] = ['tipo' => 'subtotal', 'cuenta' => $c['cuenta'],
                        'debe' => $c['totalDebe'], 'haber' => $c['totalHaber'], 'saldo' => $c['saldoFinal']];
        }
        $filas[] = ['tipo' => 'total_general', 'debe' => $mayor['totalDebe'], 'haber' => $mayor['totalHaber']];

        return $filas;
    }

    // ── COMPRAS ──
    public function comprasFilasPlanas(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $docs = DocumentoCompra::where('empresa_id', $empresa->id)
            ->whereHas('periodo', fn ($q) => $q->where('anio', $desde->year))
            ->whereBetween('mes', [$desde->month, $hasta->month])
            ->orderBy('nro')
            ->get();

        $filas = [];
        $totalExento = '0'; $totalNeto = '0'; $totalIva = '0'; $totalGeneral = '0';

        foreach ($docs as $d) {
            $filas[] = ['tipo' => 'doc', 'doc' => $d];
            $totalExento = bcadd($totalExento, (string) $d->exento, 2);
            $totalNeto = bcadd($totalNeto, (string) $d->neto, 2);
            $totalIva = bcadd($totalIva, (string) $d->iva, 2);
            $totalGeneral = bcadd($totalGeneral, (string) $d->total, 2);
        }

        $filas[] = ['tipo' => 'total_general', 'exento' => $totalExento, 'neto' => $totalNeto,
                    'iva' => $totalIva, 'total' => $totalGeneral];

        return $filas;
    }

    // ── HONORARIOS ──
    public function honorariosFilasPlanas(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $boletas = BoletaHonorario::where('empresa_id', $empresa->id)
            ->whereHas('periodo', fn ($q) => $q->where('anio', $desde->year))
            ->whereBetween('mes', [$desde->month, $hasta->month])
            ->orderBy('nro')
            ->get();

        $filas = [];
        $totalBrutos = '0'; $totalRetencion = '0'; $totalGeneral = '0';

        foreach ($boletas as $b) {
            $filas[] = ['tipo' => 'boleta', 'boleta' => $b];
            $totalBrutos = bcadd($totalBrutos, (string) $b->brutos, 2);
            $totalRetencion = bcadd($totalRetencion, (string) $b->retencion, 2);
            $totalGeneral = bcadd($totalGeneral, (string) $b->total, 2);
        }

        $filas[] = ['tipo' => 'total_general', 'brutos' => $totalBrutos,
                    'retencion' => $totalRetencion, 'total' => $totalGeneral];

        return $filas;
    }

    // ── REMUNERACIONES ──
    public function remuneracionesFilasPlanas(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $trabajadores = RemuneracionTrabajador::where('empresa_id', $empresa->id)
            ->whereHas('periodo', fn ($q) => $q->where('anio', $desde->year))
            ->whereBetween('mes', [$desde->month, $hasta->month])
            ->orderBy('mes')->orderBy('nro')
            ->get();

        $filas = [];
        $campos = ['sueldo','gratificacion','movilizacion','colacion','otros_haberes',
                'total_haberes','afp','salud','cesantia','impuesto_unico',
                'prestamo','anticipo','liquido'];
        $totales = array_fill_keys($campos, '0');

        foreach ($trabajadores as $t) {
            $filas[] = ['tipo' => 'trabajador', 'trabajador' => $t];
            foreach ($campos as $c) {
                $totales[$c] = bcadd($totales[$c], (string) $t->$c, 2);
            }
        }

        $filas[] = array_merge(['tipo' => 'total_general'], $totales);

        return $filas;
    }

    // ── VENTAS ──
    public function ventasFilasPlanas(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $docs = DocumentoVenta::where('empresa_id', $empresa->id)
            ->whereHas('periodo', fn ($q) => $q->where('anio', $desde->year))
            ->whereBetween('mes', [$desde->month, $hasta->month])
            ->orderBy('nro')
            ->get();

        $filas = [];
        $totalExento = '0'; $totalNeto = '0'; $totalIva = '0'; $totalGeneral = '0';

        foreach ($docs as $d) {
            $filas[] = ['tipo' => 'doc', 'doc' => $d];
            $totalExento = bcadd($totalExento, (string) $d->exento, 2);
            $totalNeto = bcadd($totalNeto, (string) $d->neto, 2);
            $totalIva = bcadd($totalIva, (string) $d->iva, 2);
            $totalGeneral = bcadd($totalGeneral, (string) $d->total, 2);
        }

        $filas[] = ['tipo' => 'total_general', 'exento' => $totalExento, 'neto' => $totalNeto,
                    'iva' => $totalIva, 'total' => $totalGeneral];

        return $filas;
    }

    /** Version aplanada del diario, mismo criterio. */
    public function diarioFilasPlanas(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $diario = $this->diarioCompleto($empresa, $desde, $hasta);
        $filas = [];

        foreach ($diario['comprobantes'] as $c) {
            foreach ($c->movimientos as $m) {
                $filas[] = ['tipo' => 'movimiento', 'comprobante' => $c, 'mov' => $m];
            }
            $filas[] = ['tipo' => 'subtotal_asiento', 'comprobante' => $c];
        }
        $filas[] = ['tipo' => 'total_general', 'debe' => $diario['totalDebe'], 'haber' => $diario['totalHaber']];

        return $filas;
    }
}
