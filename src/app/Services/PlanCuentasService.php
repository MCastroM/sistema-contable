<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cuenta;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

class PlanCuentasService
{
    /**
     * El primer dígito del código determina la clase contable.
     * (4 y 5 son ambas 'resultado': ganancias y pérdidas.)
     */
    private const CLASE_POR_DIGITO = [
        '1' => 'activo',
        '2' => 'pasivo',
        '3' => 'patrimonio',
        '4' => 'resultado',
        '5' => 'resultado',
    ];

    /**
     * Instala la plantilla completa (config/plan_cuentas.php) en una empresa.
     * Devuelve la cantidad de cuentas creadas.
     *
     * Uso:  app(PlanCuentasService::class)->instalarEn($empresa);
     */
    public function instalarEn(Empresa $empresa): int
    {
        if ($empresa->cuentas()->exists()) {
            throw new \RuntimeException(
                "La empresa {$empresa->razon_social} ya tiene plan de cuentas. " .
                "La instalación de plantilla es solo para empresas nuevas."
            );
        }

        $plantilla = config('plan_cuentas');

        if (empty($plantilla)) {
            throw new \RuntimeException('No se encontró config/plan_cuentas.php');
        }

   // Transacción: cuentas + auditoría son UN acto indivisible.
        // Si la auditoría falla, tampoco se crean las cuentas.
        $total = DB::transaction(function () use ($empresa, $plantilla) {
            $total = $this->crearNivel($empresa, $plantilla, null);

            Auditoria::registrar(
                accion: 'plan_cuentas.instalar',
                motivo: "Plantilla estándar instalada ({$total} cuentas)",
                empresaId: $empresa->id,
            );

            return $total;
        });

        return $total;
    }

    /**
     * Recorre la plantilla recursivamente creando cada cuenta
     * enlazada a su padre. La clase se deduce del primer dígito
     * y la imputabilidad de no tener hijas (solo las hojas imputan).
     */
    private function crearNivel(Empresa $empresa, array $definiciones, ?Cuenta $padre): int
    {
        $total = 0;

        foreach ($definiciones as $def) {
            $hijas = $def['hijas'] ?? [];

            $cuenta = $empresa->cuentas()->create([
                'padre_id'  => $padre?->id,
                'codigo'    => $def['codigo'],
                'nombre'    => $def['nombre'],
                'clase'     => self::CLASE_POR_DIGITO[substr($def['codigo'], 0, 1)],
                'imputable' => empty($hijas),
            ]);

            $total += 1 + $this->crearNivel($empresa, $hijas, $cuenta);
        }

        return $total;
    }
}
