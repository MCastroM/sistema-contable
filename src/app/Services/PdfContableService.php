<?php

namespace App\Services;

use App\Models\Empresa;
use Carbon\Carbon;

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
