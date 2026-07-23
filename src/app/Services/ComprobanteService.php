<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Comprobante;
use App\Models\Cuenta;
use App\Models\Empresa;
use App\Models\Periodo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComprobanteService
{
    /**
     * Crea un comprobante en estado BORRADOR con sus líneas.
     *
     * $lineas: arreglo de arreglos con claves:
     *   cuenta_id (int), debe (num), haber (num),
     *   glosa (string, opcional), centro_costo_id (int, opcional)
     *
     * Un borrador PUEDE estar descuadrado (se está trabajando en él);
     * la cuadratura se exige recién al aprobar.
     */
    public function crearBorrador(
        Empresa $empresa,
        string $tipo,
        Carbon|string $fecha,
        string $glosa,
        array $lineas,
    ): Comprobante {
        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);

        $periodo = $empresa->periodo($fecha->year)
            ?? throw new \RuntimeException(
                "No existe período {$fecha->year} para {$empresa->razon_social}. Créalo primero."
            );

        if (! $periodo->admiteFecha($fecha)) {
            throw new \RuntimeException(
                "La fecha {$fecha->format('d-m-Y')} no es admitida: período cerrado o fecha bloqueada."
            );
        }

        $this->validarLineas($empresa, $lineas);

        return DB::transaction(function () use ($empresa, $periodo, $tipo, $fecha, $glosa, $lineas) {

            /// Mutex por período: bloqueamos la FILA del período para
            // serializar la asignación de correlativos (PostgreSQL no
            // permite FOR UPDATE junto a funciones de agregación).
            Periodo::lockForUpdate()->findOrFail($periodo->id);

            $ultimo = Comprobante::where('empresa_id', $empresa->id)
                ->where('tipo', $tipo)
                ->where('periodo_id', $periodo->id)
                ->max('numero');

            $comprobante = Comprobante::create([
                'empresa_id' => $empresa->id,
                'periodo_id' => $periodo->id,
                'tipo'       => $tipo,
                'numero'     => ($ultimo ?? 0) + 1,
                'fecha'      => $fecha->toDateString(),
                'glosa'      => $glosa,
                'estado'     => Comprobante::BORRADOR,
                'creado_por' => auth()->id(),
            ]);

            $comprobante->movimientos()->createMany($lineas);

            Auditoria::registrar('comprobante.crear', $comprobante);

            return $comprobante->load('movimientos.cuenta');
        });
    }

    /**
     * Aprueba un borrador: aquí se hace cumplir la partida doble.
     * Un comprobante aprobado queda inmutable (solo anulable).
     */
    public function aprobar(Comprobante $comprobante): Comprobante
    {
        return DB::transaction(function () use ($comprobante) {
            // Recargar con lock: nadie más puede aprobarlo/modificarlo en paralelo
            $comprobante = Comprobante::lockForUpdate()->findOrFail($comprobante->id);

            if (! $comprobante->esBorrador()) {
                throw new \RuntimeException(
                    "Solo se aprueban borradores. Este comprobante está '{$comprobante->estado}'."
                );
            }

            // Re-verificar la fecha: el bloqueo pudo haberse movido
            // entre la creación del borrador y este momento.
            if (! $comprobante->periodo->admiteFecha($comprobante->fecha)) {
                throw new \RuntimeException(
                    'La fecha del comprobante ya no es admitida por el período (cerrado o bloqueado).'
                );
            }

            // LA REGLA SAGRADA: suma(debe) = suma(haber), mínimo 2 líneas.
            if (! $comprobante->estaCuadrado()) {
                throw new \RuntimeException(sprintf(
                    'Comprobante descuadrado: debe $%s vs haber $%s. No se puede aprobar.',
                    number_format((float) $comprobante->totalDebe(), 2, ',', '.'),
                    number_format((float) $comprobante->totalHaber(), 2, ',', '.'),
                ));
            }

            $comprobante->update([
                'estado'       => Comprobante::APROBADO,
                'aprobado_por' => auth()->id(),
                'aprobado_at'  => now(),
            ]);

            Auditoria::registrar('comprobante.aprobar', $comprobante, cambios: [
                'folio' => $comprobante->folio(),
                'total' => $comprobante->totalDebe(),
            ]);

            return $comprobante;
        });
    }

    /**
     * Anula un comprobante APROBADO. Motivo obligatorio (queda en bitácora).
     * El comprobante no se borra: queda visible con estado 'anulado'.
     */
    public function anular(Comprobante $comprobante, string $motivo): Comprobante
    {
        if (trim($motivo) === '') {
            throw new \InvalidArgumentException('La anulación exige un motivo.');
        }

        return DB::transaction(function () use ($comprobante, $motivo) {
            $comprobante = Comprobante::lockForUpdate()->findOrFail($comprobante->id);

            if ($comprobante->estado !== Comprobante::APROBADO) {
                throw new \RuntimeException('Solo se anulan comprobantes aprobados.');
            }

            if (! $comprobante->periodo->admiteFecha($comprobante->fecha)) {
                throw new \RuntimeException(
                    'No se puede anular: la fecha está en período cerrado o bloqueado. ' .
                    'Corresponde un comprobante de reversa en el período vigente.'
                );
            }

            $comprobante->update(['estado' => Comprobante::ANULADO]);

            Auditoria::registrar('comprobante.anular', $comprobante, motivo: $motivo, cambios: [
                'folio' => $comprobante->folio(),
            ]);

            return $comprobante;
        });
    }

    /**
     * Elimina físicamente un BORRADOR (sus líneas caen en cascada).
     * Los aprobados jamás se eliminan.
     */
    public function eliminarBorrador(Comprobante $comprobante): void
    {
        DB::transaction(function () use ($comprobante) {
            $comprobante = Comprobante::lockForUpdate()->findOrFail($comprobante->id);

            if (! $comprobante->esBorrador()) {
                throw new \RuntimeException('Solo se eliminan borradores.');
            }

            Auditoria::registrar('comprobante.eliminar_borrador', $comprobante, cambios: [
                'folio' => $comprobante->folio(),
                'glosa' => $comprobante->glosa,
            ]);

            $comprobante->delete();
        });
    }

    /**
     * Validaciones de las líneas ANTES de tocar la base:
     * - mínimo 2 líneas
     * - cada línea: debe XOR haber, montos > 0
     * - cuentas existentes, imputables, activas y DE LA MISMA EMPRESA
     */
    private function validarLineas(Empresa $empresa, array $lineas): void
    {
        if (count($lineas) < 2) {
            throw new \InvalidArgumentException('Un comprobante requiere al menos 2 líneas.');
        }

        $cuentaIds = collect($lineas)->pluck('cuenta_id')->unique();

        $validas = Cuenta::whereIn('id', $cuentaIds)
            ->where('empresa_id', $empresa->id)
            ->imputables()
            ->pluck('id');

        $invalidas = $cuentaIds->diff($validas);
        if ($invalidas->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'Cuentas inválidas (no existen, no imputables, inactivas o de otra empresa): ids ' .
                $invalidas->implode(', ')
            );
        }

        foreach ($lineas as $i => $linea) {
            $debe  = (string) ($linea['debe'] ?? 0);
            $haber = (string) ($linea['haber'] ?? 0);

            $tieneDebe  = bccomp($debe, '0', 2) === 1;
            $tieneHaber = bccomp($haber, '0', 2) === 1;

            if ($tieneDebe === $tieneHaber) {   // ambos o ninguno
                throw new \InvalidArgumentException(
                    'Línea ' . ($i + 1) . ': debe usar debe O haber (uno de los dos, mayor a cero).'
                );
            }
        }
    }
}
