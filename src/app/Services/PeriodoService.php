<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Comprobante;
use App\Models\Empresa;
use App\Models\Periodo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodoService
{
    /** Crea (si no existe) el período de un año para una empresa. */
    public function abrir(Empresa $empresa, int $anio): Periodo
    {
        return $empresa->periodos()->firstOrCreate(['anio' => $anio]);
    }

    /**
     * Mueve el cursor de bloqueo: nada con fecha <= $fecha podrá
     * crearse, aprobarse ni anularse. Típico: tras declarar un F29.
     */
    public function bloquearHasta(Periodo $periodo, Carbon|string $fecha, ?string $motivo = null): Periodo
    {
        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);

        if ($periodo->estaCerrado()) {
            throw new \RuntimeException('El período está cerrado: no aplica mover el bloqueo.');
        }

        if ($fecha->year !== $periodo->anio) {
            throw new \InvalidArgumentException(
                "La fecha de bloqueo debe pertenecer al año {$periodo->anio}."
            );
        }

        return DB::transaction(function () use ($periodo, $fecha, $motivo) {
            $anterior = $periodo->fecha_bloqueo?->toDateString();

            $periodo->update(['fecha_bloqueo' => $fecha->toDateString()]);

            Auditoria::registrar('periodo.bloquear', $periodo, motivo: $motivo, cambios: [
                'fecha_bloqueo' => [$anterior, $fecha->toDateString()],
            ]);

            return $periodo;
        });
    }

    /**
     * Cierre del año comercial: acción manual y deliberada del contador.
     * Requisito: cero borradores pendientes en el período.
     */
    public function cerrar(Periodo $periodo, ?string $motivo = null): Periodo
    {
        return DB::transaction(function () use ($periodo, $motivo) {
            $periodo = Periodo::lockForUpdate()->findOrFail($periodo->id);

            if ($periodo->estaCerrado()) {
                throw new \RuntimeException("El período {$periodo->anio} ya está cerrado.");
            }

            $borradores = $periodo->comprobantes()
                ->where('estado', Comprobante::BORRADOR)
                ->count();

            if ($borradores > 0) {
                throw new \RuntimeException(
                    "No se puede cerrar {$periodo->anio}: hay {$borradores} comprobante(s) " .
                    "en borrador. Apruébalos o elimínalos primero."
                );
            }

            $periodo->update(['estado' => Periodo::CERRADO]);

            Auditoria::registrar('periodo.cerrar', $periodo, motivo: $motivo, cambios: [
                'estado' => [Periodo::ABIERTO, Periodo::CERRADO],
            ]);

            return $periodo;
        });
    }

    /**
     * Reapertura de un año cerrado: permitida, pero JAMÁS silenciosa.
     * Motivo obligatorio; queda en bitácora con quién, cuándo y desde dónde.
     * (Cuando existan roles, esta acción quedará restringida a administradores.)
     */
    public function reabrir(Periodo $periodo, string $motivo): Periodo
    {
        if (trim($motivo) === '') {
            throw new \InvalidArgumentException(
                'La reapertura de un período cerrado exige un motivo.'
            );
        }

        return DB::transaction(function () use ($periodo, $motivo) {
            $periodo = Periodo::lockForUpdate()->findOrFail($periodo->id);

            if (! $periodo->estaCerrado()) {
                throw new \RuntimeException("El período {$periodo->anio} no está cerrado.");
            }

            $periodo->update(['estado' => Periodo::ABIERTO]);

            Auditoria::registrar('periodo.reabrir', $periodo, motivo: $motivo, cambios: [
                'estado' => [Periodo::CERRADO, Periodo::ABIERTO],
            ]);

            return $periodo;
        });
    }
}
