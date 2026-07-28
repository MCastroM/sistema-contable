<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\SaldoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class AperturaAnualPdfController extends Controller
{
    /**
     * Genera el bloque de APERTURA del año: 20 hojas en blanco (solo
     * membrete y folio) + el Balance Tributario de 8 columnas -- el
     * inicio de la secuencia completa del libro impreso.
     */
    public function generar(Request $request, Empresa $empresa, SaldoService $saldos)
    {
        $anio = (int) $request->query('anio', now()->year);
        $folioInicio = (int) $request->query('folio', 1744); // primera hoja en blanco
        $cantidadBlancas = (int) $request->query('blancas', 20);

        $desde = Carbon::create($anio, 1, 1)->startOfDay();
        $hasta = Carbon::create($anio, 12, 31)->endOfDay();

        // ── Mismo calculo que el Balance de 8 columnas standalone ──
        $indice = $saldos->indice($empresa, $desde, $hasta);

        // Ver nota en BalancePdfController: agregamos cuentas con saldo
        // arrastrado pero sin movimiento en el periodo, que indice()
        // excluye por diseño (correcto para el Mayor, no para un Balance).
        $idsConMovimiento = $indice->pluck('cuenta.id')->all();
        $todasImputables = $empresa->cuentas()->where('imputable', true)->get();

        foreach ($todasImputables as $cuenta) {
            if (in_array($cuenta->id, $idsConMovimiento, true)) {
                continue;
            }
            $anterior = $saldos->saldoAnterior($cuenta, $desde);
            $saldoAnteriorNeto = $saldos->saldoNeto($cuenta, $anterior['debe'], $anterior['haber']);
            if (bccomp($saldoAnteriorNeto, '0', 2) === 0) {
                continue;
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

            // Ver nota detallada en BalancePdfController: sin cierre de
            // ejercicio formal entre años (ABSAL no lo hacia), todas las
            // cuentas usan el saldo acumulado historico por igual -- es
            // lo unico que garantiza Deudor=Acreedor matematicamente.
            $real = $f->esDeudora ? $f->saldoFinal : bcmul($f->saldoFinal, '-1', 2);

            $saldoDeudor   = bccomp($real, '0', 2) === 1  ? $real : '0';
            $saldoAcreedor = bccomp($real, '0', 2) === -1 ? bcmul($real, '-1', 2) : '0';

            $activoCol = $pasivoCol = $perdidaCol = $ganciaCol = '0';

            if ($clase === 'activo') {
                $activoCol = $saldoDeudor; $pasivoCol = $saldoAcreedor;
            } elseif (in_array($clase, ['pasivo', 'patrimonio'])) {
                $pasivoCol = $saldoAcreedor; $activoCol = $saldoDeudor;
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

        $resultado = bcsub($totales['ganancia'], $totales['perdida'], 2);
        if (bccomp($resultado, '0', 2) === 1) {
            $plugActivo = '0'; $plugPasivo = $resultado; $plugPerdida = $resultado; $plugGanancia = '0';
        } elseif (bccomp($resultado, '0', 2) === -1) {
            $abs = bcmul($resultado, '-1', 2);
            $plugActivo = $abs; $plugPasivo = '0'; $plugPerdida = '0'; $plugGanancia = $abs;
        } else {
            $plugActivo = $plugPasivo = $plugPerdida = $plugGanancia = '0';
        }

        $resultadoEjercicio = ['activo' => $plugActivo, 'pasivo' => $plugPasivo, 'perdida' => $plugPerdida, 'ganancia' => $plugGanancia];
        $totalesIguales = [
            'debe' => $totales['debe'], 'haber' => $totales['haber'],
            'deudor' => $totales['deudor'], 'acreedor' => $totales['acreedor'],
            'activo' => bcadd($totales['activo'], $plugActivo, 2),
            'pasivo' => bcadd($totales['pasivo'], $plugPasivo, 2),
            'perdida' => bcadd($totales['perdida'], $plugPerdida, 2),
            'ganancia' => bcadd($totales['ganancia'], $plugGanancia, 2),
        ];

        // ── PASO 1: DomPDF genera el contenido "en limpio" ──
        $rutaBlancas = storage_path('app/tmp_blancas_' . uniqid() . '.pdf');
        file_put_contents($rutaBlancas, Pdf::loadView('pdf.blancas_contenido', ['cantidad' => $cantidadBlancas])
            ->setPaper('letter', 'landscape')->output());

        $rutaBalance = storage_path('app/tmp_balance_' . uniqid() . '.pdf');
        file_put_contents($rutaBalance, Pdf::loadView('pdf.balance8_contenido', compact(
            'porClase', 'totales', 'resultadoEjercicio', 'totalesIguales', 'hasta'
        ))->setPaper('letter', 'landscape')->output());

        // ── PASO 2: FPDI importa y estampa membrete+folio en cada pagina ──
        $pdf = new Fpdi('L', 'mm', [279.4, 215.9]);
        $pdf->SetAutoPageBreak(false);

        $empNombre = $this->latin1(preg_replace('/\s*\(PRUEBA\)\s*$/i', '', $empresa->razon_social));
        $empRutGiro = $this->latin1('R.U.T.: ' . $empresa->rut . ($empresa->giro ? ' GIRO: ' . $empresa->giro : ''));
        $empDireccion = $this->latin1($empresa->direccion ?? '');

        $folio = $folioInicio;

        // Hojas en blanco: SOLO membrete + folio, sin titulo
        $nPagsBlancas = $pdf->setSourceFile($rutaBlancas);
        for ($i = 1; $i <= $nPagsBlancas; $i++) {
            $this->estamparPagina($pdf, $rutaBlancas, $i, $empNombre, $empRutGiro, $empDireccion, $folio, null);
            $folio--;
        }

        // Balance: con su titulo propio de bloque (opcional, el contenido
        // ya trae su propio h1 con el titulo del balance)
        $nPagsBalance = $pdf->setSourceFile($rutaBalance);
        for ($i = 1; $i <= $nPagsBalance; $i++) {
            $this->estamparPagina($pdf, $rutaBalance, $i, $empNombre, $empRutGiro, $empDireccion, $folio, null);
            $folio--;
        }

        @unlink($rutaBlancas);
        @unlink($rutaBalance);

        $nombreArchivo = "apertura-{$empresa->rut}-{$anio}.pdf";

        return response($pdf->Output('S', $nombreArchivo), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
        ]);
    }

    private function estamparPagina(Fpdi $pdf, string $ruta, int $pagina, string $nombre, string $rutGiro, string $direccion, int $folio, ?string $titulo): void
    {
        $tplId = $pdf->importPage($pagina);
        $tam = $pdf->getTemplateSize($tplId);
        $pdf->AddPage('L', [$tam['width'], $tam['height']]);
        $pdf->useTemplate($tplId, 0, 0, $tam['width'], $tam['height']);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY(10, 6);
        $pdf->Cell(150, 5, $nombre, 0, 0, 'L');

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(10, 11);
        $pdf->Cell(180, 4, $rutGiro, 0, 0, 'L');
        $pdf->SetXY(10, 15);
        $pdf->Cell(180, 4, $direccion, 0, 0, 'L');

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY(220, 9);
        $pdf->Cell(50, 5, $this->latin1('FOLIO N° ' . $folio), 0, 0, 'R');

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.25);
        $pdf->Line(10, 20, 270, 20);

        if ($titulo) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetXY(0, 22);
            $pdf->Cell(279.4, 5, $titulo, 0, 0, 'C');
        }
    }

    private function latin1(string $texto): string
    {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }
}
