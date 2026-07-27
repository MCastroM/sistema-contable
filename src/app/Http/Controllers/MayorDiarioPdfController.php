<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\PdfContableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class MayorDiarioPdfController extends Controller
{
    private const FILAS_POR_PAGINA = 55;

    public function generar(Request $request, Empresa $empresa, PdfContableService $servicio)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : now()->endOfMonth();
        $folioInicioMayor = (int) $request->query('folio_mayor', 1);
        $folioInicioDiario = (int) $request->query('folio_diario', $folioInicioMayor);

        // Ajustables por URL para calibrar sin tener que redesplegar --
        // ej: ?filas_mayor=55&filas_diario=63
        $filasMayor = (int) $request->query('filas_mayor', self::FILAS_POR_PAGINA);
        $filasDiario = (int) $request->query('filas_diario', self::FILAS_POR_PAGINA);

        $filasMayorArr = $servicio->mayorFilasPlanas($empresa, $desde, $hasta);
        $filasDiarioArr = $servicio->diarioFilasPlanas($empresa, $desde, $hasta);

        // El "TOTAL GENERAL AÑO XXXX" es el acumulado del AÑO COMPLETO,
        // no solo del mes solicitado -- se consulta aparte y se agrega
        // al final del Mayor (igual que en el libro original ya impreso).
        // Envuelto en try/catch: si algo falla, se VE el error en el PDF
        // en vez de desaparecer en silencio.
        $anio = (int) $hasta->format('Y');
        try {
            $inicioAnio = Carbon::create($anio, 1, 1)->startOfDay();
            $finAnio = Carbon::create($anio, 12, 31)->endOfDay();
            $mayorAnual = $servicio->mayorCompleto($empresa, $inicioAnio, $finAnio);
            $debeAnual = $mayorAnual['totalDebe'];
            $haberAnual = $mayorAnual['totalHaber'];
        } catch (\Throwable $e) {
            $debeAnual = 'ERROR: ' . $e->getMessage();
            $haberAnual = 'ERROR';
        }

        $filasMayorArr[] = [
            'tipo' => 'total_general_anio',
            'anio' => $anio,
            'debe' => $debeAnual,
            'haber' => $haberAnual,
        ];

        // IMPORTANTE: la fila de cierre (total general) NO debe generar
        // una pagina nueva solo para ella -- se pega a la ultima pagina
        // de contenido real. Separamos esa fila, dividimos el resto en
        // bloques, y la agregamos al final del ULTIMO bloque.
        $filaCierreMayor = array_pop($filasMayorArr);
        $filaCierreDiario = array_pop($filasDiarioArr);

        // ── Auto-calibracion: en vez de adivinar cuantas filas caben
        //    por pagina, RENDERIZAMOS y MEDIMOS las paginas reales que
        //    genero DomPDF. Si hubo desborde (mas paginas reales que
        //    bloques esperados), reducimos y reintentamos -- hasta que
        //    coincidan exacto. Elimina el "prueba y error" manual. ──
        [$rutaMayor, $bloquesMayor] = $this->generarConCalibracion(
            'pdf.mayor_contenido', 'bloquesMayor', $filasMayorArr, $filaCierreMayor, $filasMayor
        );
        [$rutaDiario, $bloquesDiario] = $this->generarConCalibracion(
            'pdf.diario_contenido', 'bloquesDiario', $filasDiarioArr, $filaCierreDiario, $filasDiario
        );

        // ── PASO 2: FPDI importa cada página y le ESTAMPA el encabezado
        //    encima, en un bucle PHP normal — sin callbacks ni scripts
        //    embebidos, con control total sobre cada página. ──
        $pdf = new Fpdi('L', 'mm', [279.4, 215.9]); // Carta apaisado
        $pdf->SetAutoPageBreak(false);

        $empNombre = $this->latin1(preg_replace('/\s*\(PRUEBA\)\s*$/i', '', $empresa->razon_social));
        $empRutGiro = $this->latin1('R.U.T.: ' . $empresa->rut . ($empresa->giro ? ' GIRO: ' . $empresa->giro : ''));
        $empDireccion = $this->latin1($empresa->direccion ?? '');
        $tituloMayor = $this->latin1('LIBRO MAYOR - ' . mb_strtoupper($desde->translatedFormat('F Y')));
        $tituloDiario = $this->latin1('LIBRO DIARIO - ' . mb_strtoupper($desde->translatedFormat('F Y')));

        $estamparBloque = function (string $rutaArchivo, string $titulo, int $folioInicio) use (
            $pdf, $empNombre, $empRutGiro, $empDireccion
        ) {
            $folio = $folioInicio;
            $nPags = $pdf->setSourceFile($rutaArchivo);
            for ($i = 1; $i <= $nPags; $i++) {
                $tplId = $pdf->importPage($i);
                $tam = $pdf->getTemplateSize($tplId);
                $pdf->AddPage('L', [$tam['width'], $tam['height']]);
                $pdf->useTemplate($tplId, 0, 0, $tam['width'], $tam['height']);

                // ── Encabezado dibujado con control total, garantizado
                //    en CADA página (esto es un for normal de PHP) ──
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
        };

        $estamparBloque($rutaMayor, $tituloMayor, $folioInicioMayor);
        $estamparBloque($rutaDiario, $tituloDiario, $folioInicioDiario);

        @unlink($rutaMayor);
        @unlink($rutaDiario);

        $nombreArchivo = "mayor-diario-{$empresa->rut}-{$hasta->format('Y-m')}.pdf";

        return response($pdf->Output('S', $nombreArchivo), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
        ]);
    }

    /**
     * Renderiza el contenido y MIDE cuantas paginas reales genero
     * DomPDF (con FPDI, sin necesidad de importar cada pagina, solo
     * contar). Si hubo desborde (mas paginas reales que bloques
     * armados), reduce las filas por pagina y reintenta -- hasta que
     * el conteo real coincida exacto con lo esperado. Asi eliminamos
     * el ajuste manual por prueba y error.
     */
    private function generarConCalibracion(
        string $vista, string $nombreVariable, array $filasPlanas, array $filaCierre, int $filasIniciales
    ): array {
        $intento = $filasIniciales;
        $minimo = max(10, (int) ($filasIniciales / 3)); // limite de seguridad, evita loop infinito
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
                // Sin desborde: las paginas reales caben en los bloques
                // esperados (pueden ser incluso menos, nunca mas).
                break;
            }

            $intento -= 3; // reducir y reintentar
        }

        return [$ruta, $bloques];
    }

    /**
     * FPDF (usado por FPDI para dibujar el encabezado) espera texto en
     * Latin-1, no UTF-8 -- sin esto, tildes y el simbolo "°" salen mal
     * (ej: "N°" se veia como "NÂ°").
     */
    private function latin1(string $texto): string
    {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }
}
