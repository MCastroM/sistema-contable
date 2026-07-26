<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Periodo;
use App\Models\RemuneracionTrabajador;
use Illuminate\Support\Facades\DB;

class CentralizacionRemuneracionesService
{
    public function __construct(
        private ComprobanteService $comprobantes,
    ) {}

    /**
     * Genera el comprobante mensual de remuneraciones, replicando el
     * patrón visto en ABSAL (comprobante "CENTRALIZA LIBRO DE
     * REMUNERACIONES"):
     *
     *   DEBE  Remuneraciones (gasto)         = suma(total_haberes)
     *   HABER Impuesto Único (pasivo)        = suma(impuesto_unico)
     *   HABER Leyes Sociales (pasivo)        = suma(afp+salud+pactado+cesantía)
     *   HABER Anticipo de Sueldo (activo)*   = suma(anticipo)   [reduce el activo]
     *   HABER Préstamo Personal (activo)*    = suma(prestamo)   [reduce el activo]
     *   HABER Remuneraciones por Pagar       = suma(liquido)
     *
     * (*) Anticipo y Préstamo son cuentas de ACTIVO: al descontarse del
     * sueldo, se abonan (haber) para saldar lo que la empresa ya había
     * entregado antes al trabajador.
     */
    public function centralizar(Empresa $empresa, Periodo $periodo, int $mes): \App\Models\Comprobante
    {
        $requeridas = [
            'cuenta_remuneraciones_pagar_id' => 'Remuneraciones por Pagar',
            'cuenta_impuesto_unico_id'       => 'Impuesto Único',
            'cuenta_leyes_sociales_id'       => 'Leyes Sociales',
        ];
        foreach ($requeridas as $campo => $nombre) {
            if (! $empresa->$campo) {
                throw new \RuntimeException("Configura primero la cuenta '{$nombre}' en la empresa.");
            }
        }

        // La cuenta de GASTO (Remuneraciones) se resuelve por nombre en
        // el plan, en vez de agregar otra columna de configuración: es
        // la única de las 6 que siempre se llama igual en el plan estándar.
        $cuentaGasto = $empresa->cuentas()
            ->whereIn('nombre', ['Remuneraciones', 'Sueldos'])
            ->where('clase', 'resultado')->first();

        if (! $cuentaGasto) {
            throw new \RuntimeException(
                "No se encontró una cuenta de gasto llamada 'Remuneraciones' o 'Sueldos' en el plan de {$empresa->razon_social}."
        );
}

        $filas = RemuneracionTrabajador::where('empresa_id', $empresa->id)
            ->where('periodo_id', $periodo->id)
            ->where('mes', $mes)
            ->whereNull('comprobante_id')
            ->get();

        if ($filas->isEmpty()) {
            throw new \RuntimeException('No hay remuneraciones pendientes de centralizar para ese mes.');
        }

        $sumar = fn (string $campo) => $filas->reduce(fn ($acc, $f) => bcadd($acc, (string) $f->$campo, 2), '0');

        $totalHaberes = $sumar('total_haberes');
        $totalImpuesto = $sumar('impuesto_unico');
        $totalPrevisional = $filas->reduce(
            fn ($acc, $f) => bcadd($acc, $f->totalPrevisional(), 2), '0'
        );
        $totalAnticipo = $sumar('anticipo');
        $totalPrestamo = $sumar('prestamo');
        $totalAhorro = $sumar('cuenta_ahorro');
        $totalLiquido = $sumar('liquido');

        $lineas = [];
        $lineas[] = ['cuenta_id' => $cuentaGasto->id, 'debe' => $totalHaberes, 'haber' => 0,
                     'glosa' => 'Centraliza libro de remuneraciones'];

        if (bccomp($totalImpuesto, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $empresa->cuenta_impuesto_unico_id, 'debe' => 0, 'haber' => $totalImpuesto,
                         'glosa' => 'Impuesto único del mes'];
        }
        if (bccomp($totalPrevisional, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $empresa->cuenta_leyes_sociales_id, 'debe' => 0, 'haber' => $totalPrevisional,
                         'glosa' => 'Leyes sociales del mes'];
        }
        if ($empresa->cuenta_anticipo_sueldo_id && bccomp($totalAnticipo, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $empresa->cuenta_anticipo_sueldo_id, 'debe' => 0, 'haber' => $totalAnticipo,
                         'glosa' => 'Descuento anticipos del mes'];
        }
        if ($empresa->cuenta_prestamo_personal_id && bccomp($totalPrestamo, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $empresa->cuenta_prestamo_personal_id, 'debe' => 0, 'haber' => $totalPrestamo,
                         'glosa' => 'Descuento préstamos del mes'];
        }
        if ($empresa->cuenta_ahorro_trabajador_id && bccomp($totalAhorro, '0', 2) === 1) {
            $lineas[] = ['cuenta_id' => $empresa->cuenta_ahorro_trabajador_id, 'debe' => 0, 'haber' => $totalAhorro,
                         'glosa' => 'Descuento cuenta de ahorro del mes'];
        }

        $lineas[] = ['cuenta_id' => $empresa->cuenta_remuneraciones_pagar_id, 'debe' => 0, 'haber' => $totalLiquido,
                     'glosa' => 'Centraliza libro de remuneraciones'];

        return DB::transaction(function () use ($empresa, $periodo, $mes, $lineas, $filas) {
            $fecha = \Carbon\Carbon::create($periodo->anio, $mes, 1)->endOfMonth();

            $comprobante = $this->comprobantes->crearBorrador(
                $empresa, 'T', $fecha,
                sprintf('Centralización Libro de Remuneraciones %02d/%d', $mes, $periodo->anio),
                $lineas,
            );

            $this->comprobantes->aprobar($comprobante);

            RemuneracionTrabajador::whereIn('id', $filas->pluck('id'))
                ->update(['comprobante_id' => $comprobante->id]);

            return $comprobante;
        });
    }
}
