<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\PdfContableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class LibroAuxiliarPdfController extends Controller
{
    private const TIPOS_VALIDOS = ['compras', 'honorarios', 'remuneraciones', 'ventas'];

    private const TITULOS = [
        'compras'         => 'LIBRO DE COMPRAS',
        'honorarios'       => 'LIBRO DE HONORARIOS',
        'remuneraciones'  => 'LIBRO DE REMUNERACIONES',
        'ventas'          => 'LIBRO DE VENTAS',
    ];

    /**
     * PDF de uno de los 4 libros auxiliares (compras/honorarios/
     * remuneraciones/ventas), con la misma tecnica de auto-calibracion
     * FPDI que ya funciona en Mayor+Diario.
     */
    public function generar(Request $request, Empresa $empresa, string $tipo, PdfContableService $servicio)
    {
        abort_unless(in_array($tipo, self::TIPOS_VALIDOS, true), 404, 'Libro no reconocido.');

        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : now()->endOfMonth();
        $folioInicio = (int) $request->query('folio', 1);
        $filasIniciales = (int) $request->query('filas', 55);

        $metodo = match ($tipo) {
            'compras' => 'comprasFilasPlanas',
            'honorarios' => 'honorariosFilasPlanas',
            'remuneraciones' => 'remuneracionesFilasPlanas',
            'ventas' => 'ventasFilasPlanas',
        };

        $filasArr = $servicio->$metodo($empresa, $desde, $hasta);
        $filaCierre = array_pop($filasArr);

        [$ruta, $bloques] = $this->generarConCalibracion(
            "pdf.{$tipo}_contenido", 'bloques', $filasArr, $filaCierre, $filasIniciales
        );

        $pdf = new Fpdi('L', 'mm', [279.4, 215.9]);
        $pdf->SetAutoPageBreak(false);

        $empNombre = $this->latin1(preg_replace('/\s*\(PRUEBA\)\s*$/i', '', $empresa->razon_social));
        $empRutGiro = $this->latin1('R.U.T.: ' . $empresa->rut . ($empresa->giro ? ' GIRO: ' . $empresa->giro : ''));
        $empDireccion = $this->latin1($empresa->direccion ?? '');
        $titulo = $this->latin1(self::TITULOS[$tipo] . ' - ' . mb_strtoupper($desde->translatedFormat('F Y')));

        $folio = $folioInicio;
        $nPags = $pdf->setSourceFile($ruta);
        for ($i = 1; $i <= $nPags; $i++) {
            $tplId = $pdf->importPage($i);
            $tam = $pdf->getTemplateSize($tplId);
            $pdf->AddPage('L', [$tam['width'], $tam['height']]);
            $pdf->useTemplate($tplId, 0, 0, $tam['width'], $tam['height']);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetXY(10, 6);
            $pdf->Cell(150, 5, $empNombre, 0, 0, 'L');

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(10, 11);
            $pdf->Cell(180, 4, $empRutGiro, 0, 0, 'L');
            $pdf->SetXY(10, 15);
            $pdf->Cell(180, 4, $empDireccion, 0, 0, 'L');

            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetXY(220, 9);
            $pdf->Cell(50, 5, $this->latin1('FOLIO N° ' . $folio), 0, 0, 'R');

            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.25);
            $pdf->Line(10, 20, 270, 20);

            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetXY(0, 22);
            $pdf->Cell(279.4, 5, $titulo, 0, 0, 'C');

            $folio++;
        }

        @unlink($ruta);

        $nombreArchivo = "{$tipo}-{$empresa->rut}-{$hasta->format('Y-m')}.pdf";

        return response($pdf->Output('S', $nombreArchivo), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
        ]);
    }

    /**
     * Renderiza y MIDE cuantas paginas reales genero DomPDF. Si hubo
     * desborde, reduce las filas por pagina y reintenta -- misma tecnica
     * de auto-calibracion ya probada en MayorDiarioPdfController.
     */
    private function generarConCalibracion(
        string $vista, string $nombreVariable, array $filasPlanas, array $filaCierre, int $filasIniciales
    ): array {
        $intento = $filasIniciales;
        $minimo = max(10, (int) ($filasIniciales / 3));
        $ruta = null;
        $bloques = [];

        while ($intento >= $minimo) {
            $bloques = array_chunk($filasPlanas, $intento);
            if (empty($bloques)) {
                $bloques[] = [];
            }
            $bloques[count($bloques) - 1][] = $filaCierre;

            if ($ruta) {
                @unlink($ruta);
            }
            $ruta = storage_path('app/tmp_' . uniqid() . '.pdf');
            file_put_contents($ruta, Pdf::loadView($vista, [$nombreVariable => $bloques])
                ->setPaper('letter', 'landscape')->output());

            $medidor = new Fpdi();
            $paginasReales = $medidor->setSourceFile($ruta);

            if ($paginasReales <= count($bloques)) {
                break;
            }

            $intento -= 3;
        }

        return [$ruta, $bloques];
    }

    private function latin1(string $texto): string
    {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }
}
