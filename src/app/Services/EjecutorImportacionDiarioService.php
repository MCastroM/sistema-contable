<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\Empresa;
use App\Models\MapeoCuenta;
use App\Models\Periodo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EjecutorImportacionDiarioService
{
    public function __construct(
        private ComprobanteService $comprobantes,
    ) {}

    /**
     * Importa un lote de asientos agrupados por N° de comprobante original.
     *
     * IMPORTANTE: cada comprobante se procesa en SU PROPIA transacción.
     * Un asiento descuadrado o con error NO detiene el resto del lote —
     * se reporta y se sigue. Para una importación de cientos de asientos
     * históricos, esto es indispensable: no puedes perder 400 asientos
     * buenos porque el 37 tenía un problema.
     *
     * $grupos: Collection agrupada por 'comprobante' (ver
     *   ImportadorDiarioService::agruparPorComprobante), ya con 'cod'
     *   traducido a cuenta_id vía el mapeo.
     *
     * Devuelve: ['ok' => int, 'errores' => [ ['comprobante' => n, 'error' => msg], ... ]]
     */
    public function importarLote(Empresa $empresa, Periodo $periodo, string $tipo, Collection $grupos): array
    {
        $ok = 0;
        $errores = [];

        foreach ($grupos as $numeroOrigen => $lineasCrudas) {
            try {
                $this->importarUnComprobante($empresa, $periodo, $tipo, (string) $numeroOrigen, $lineasCrudas);
                $ok++;
            } catch (\Throwable $e) {
                $errores[] = ['comprobante' => $numeroOrigen, 'error' => $e->getMessage()];
            }
        }

        return ['ok' => $ok, 'errores' => $errores];
    }

    private function importarUnComprobante(
        Empresa $empresa, Periodo $periodo, string $tipo, string $numeroOrigen, Collection $lineasCrudas
    ): Comprobante {
        // Todas las líneas de un mismo comprobante deben compartir fecha;
        // si vinieran distintas (dato sucio), usamos la primera válida.
        $fecha = $lineasCrudas->pluck('fecha')->filter()->first();
        if (! $fecha) {
            throw new \RuntimeException("Sin fecha válida en ninguna línea (revisar 'fecha_raw' original).");
        }

        $glosa = $lineasCrudas->pluck('glosa')->filter()->first() ?: "Importado del libro diario, comprobante {$numeroOrigen}";

        $lineas = $lineasCrudas->map(fn ($l) => [
            'cuenta_id' => $l['cuenta_id'],
            'debe'      => $l['debe'],
            'haber'     => $l['haber'],
            'glosa'     => $l['glosa'] ?: null,
        ])->values()->all();

        return DB::transaction(function () use ($empresa, $periodo, $tipo, $fecha, $glosa, $lineas, $numeroOrigen) {
            // crearBorrador ya valida: cuentas imputables, de la empresa,
            // debe XOR haber por línea, y admiteFecha() del período.
            $comprobante = $this->comprobantes->crearBorrador($empresa, $tipo, $fecha, $glosa, $lineas);

            // aprobar() exige la cuadratura (debe=haber) — la MISMA
            // regla que protege los comprobantes digitados a mano.
            // Si el asiento histórico viene descuadrado, esto lanza
            // la excepción y el comprobante queda como borrador (no
            // se pierde: se puede corregir manualmente después).
            $this->comprobantes->aprobar($comprobante);

            $comprobante->update(['numero_origen' => $numeroOrigen]);

            return $comprobante;
        });
    }

    /** Traduce 'cod' -> 'cuenta_id' usando el mapeo ya resuelto para la empresa. */
    public function resolverCuentas(Empresa $empresa, Collection $filas): Collection
    {
        $mapeo = MapeoCuenta::where('empresa_id', $empresa->id)
            ->where('libro', ImportadorDiarioService::LIBRO)
            ->pluck('cuenta_id', 'codigo_origen');

        return $filas->map(fn ($f) => array_merge($f, [
            'cuenta_id' => $mapeo->get($f['cod']),
        ]));
    }
}
