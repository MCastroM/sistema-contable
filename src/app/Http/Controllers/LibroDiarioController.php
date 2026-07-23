<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LibroDiarioController extends Controller
{
    /**
     * Libro diario en orden cronológico.
     *
     * Filtros (query string):
     *   desde, hasta        -> rango de fechas (por defecto: mes en curso)
     *   tipo                -> I, E o T
     *   incluir_borradores  -> "1" vista previa: intercala borradores
     *   incluir_anulados    -> "1" muestra anulados (tachados, sin sumar)
     *
     * Los borradores se intercalan por fecha pero NUNCA suman a los
     * totales oficiales: alimentan una segunda columna "proyectado".
     */
    public function index(Request $request)
    {
        $empresa = $this->empresaActiva();

        $desde = $request->filled('desde')
            ? Carbon::parse($request->desde)
            : now()->startOfMonth();

        $hasta = $request->filled('hasta')
            ? Carbon::parse($request->hasta)
            : now()->endOfMonth();

        $incluirBorradores = $request->boolean('incluir_borradores');
        $incluirAnulados   = $request->boolean('incluir_anulados');

        $estados = [Comprobante::APROBADO];
        if ($incluirBorradores) {
            $estados[] = Comprobante::BORRADOR;
        }
        if ($incluirAnulados) {
            $estados[] = Comprobante::ANULADO;
        }

        $comprobantes = $empresa->comprobantes()
            ->whereIn('estado', $estados)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->with(['movimientos.cuenta:id,codigo,nombre',
                    'movimientos.centroCosto:id,codigo,nombre'])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // Doble totalización: oficial (solo aprobados) y proyectado
        // (aprobados + borradores). Los anulados no suman en ninguna.
        $oficialDebe    = '0';
        $oficialHaber   = '0';
        $borradorDebe   = '0';
        $borradorHaber  = '0';
        $hayDescuadreBorrador = false;

        foreach ($comprobantes as $c) {
            if ($c->estado === Comprobante::ANULADO) {
                continue;
            }

            $sumaDebe  = '0';
            $sumaHaber = '0';
            foreach ($c->movimientos as $m) {
                $sumaDebe  = bcadd($sumaDebe,  (string) $m->debe,  2);
                $sumaHaber = bcadd($sumaHaber, (string) $m->haber, 2);
            }

            if ($c->estado === Comprobante::APROBADO) {
                $oficialDebe  = bcadd($oficialDebe,  $sumaDebe,  2);
                $oficialHaber = bcadd($oficialHaber, $sumaHaber, 2);
            } else {  // borrador
                $borradorDebe  = bcadd($borradorDebe,  $sumaDebe,  2);
                $borradorHaber = bcadd($borradorHaber, $sumaHaber, 2);

                // Un borrador PUEDE estar descuadrado: hay que avisarlo
                if (bccomp($sumaDebe, $sumaHaber, 2) !== 0) {
                    $hayDescuadreBorrador = true;
                }
            }
        }

        return view('reportes.libro-diario', [
            'empresa'              => $empresa,
            'comprobantes'         => $comprobantes,
            'desde'                => $desde,
            'hasta'                => $hasta,
            'tipoActivo'           => $request->tipo,
            'incluirBorradores'    => $incluirBorradores,
            'incluirAnulados'      => $incluirAnulados,
            'oficialDebe'          => $oficialDebe,
            'oficialHaber'         => $oficialHaber,
            'proyectadoDebe'       => bcadd($oficialDebe,  $borradorDebe,  2),
            'proyectadoHaber'      => bcadd($oficialHaber, $borradorHaber, 2),
            'hayDescuadreBorrador' => $hayDescuadreBorrador,
            'conteoAprobados'      => $comprobantes->where('estado', Comprobante::APROBADO)->count(),
            'conteoBorradores'     => $comprobantes->where('estado', Comprobante::BORRADOR)->count(),
        ]);
    }

    private function empresaActiva(): Empresa
    {
        $empresa = Empresa::find(session('empresa_activa_id'))
            ?? Empresa::where('activa', true)->orderBy('razon_social')->first();

        abort_if(! $empresa, 404, 'No hay empresas registradas.');

        return $empresa;
    }
}
