<?php

namespace App\Console\Commands;

use App\Services\MindicadorService;
use Illuminate\Console\Command;

class ActualizarIndicadores extends Command
{
    /**
     * Cómo se invoca:
     *   php artisan indicadores:actualizar            → valores de hoy
     *   php artisan indicadores:actualizar --anio=2025 --codigo=uf  → histórico
     */
    protected $signature = 'indicadores:actualizar
                            {--anio= : Importar histórico de un año completo}
                            {--codigo=uf : Indicador a importar cuando se usa --anio}';

    protected $description = 'Actualiza los indicadores económicos (UF, UTM, dólar, IPC, TPM) desde mindicador.cl';

    public function handle(MindicadorService $servicio): int
    {
        try {
            if ($anio = $this->option('anio')) {
                $codigo = $this->option('codigo');
                $this->info("Importando histórico de {$codigo} para el año {$anio}...");
                $total = $servicio->importarAnio($codigo, (int) $anio);
                $this->info("✔ {$total} registros importados/actualizados.");
                return self::SUCCESS;
            }

            $this->info('Consultando mindicador.cl...');
            $resumen = $servicio->actualizarDiarios();

            foreach ($resumen as $codigo => $detalle) {
                $this->line("  <info>{$codigo}</info>: {$detalle}");
            }

            $this->info('✔ Indicadores actualizados.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('✖ ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
