<?php

namespace Tests\Feature;

use App\Services\MindicadorService;
use Mockery;
use Tests\TestCase;

class ActualizarIndicadoresTest extends TestCase
{
    public function test_el_comando_muestra_los_indicadores_actualizados(): void
    {
        // 1. Crear el DOBLE: un objeto falso que finge ser MindicadorService
        $doble = Mockery::mock(MindicadorService::class);

        // 2. Programar su comportamiento: "cuando te llamen
        //    actualizarDiarios(), devuelve estos datos inventados"
        $doble->shouldReceive('actualizarDiarios')
            ->once()                          // y esperamos que te llamen EXACTAMENTE una vez
            ->andReturn([
                'uf'  => 'Unidad de Fomento (21-07-2026) = 40.850,00',
                'utm' => 'Unidad Tributaria Mensual (01-07-2026) = 71.649,00',
            ]);

        // 3. LA SUSTITUCIÓN: registrar el doble en el contenedor
        $this->app->instance(MindicadorService::class, $doble);

        // 4. Ejecutar el comando de verdad — recibirá el doble sin saberlo
        $this->artisan('indicadores:actualizar')
            ->expectsOutputToContain('uf')
            ->expectsOutputToContain('✔ Indicadores actualizados.')
            ->assertSuccessful();
    }

    public function test_el_comando_falla_ordenadamente_si_la_api_no_responde(): void
    {
        $doble = Mockery::mock(MindicadorService::class);

        // Esta vez el doble simula el DESASTRE: lanza la excepción
        $doble->shouldReceive('actualizarDiarios')
            ->once()
            ->andThrow(new \RuntimeException('No fue posible consultar mindicador.cl'));

        $this->app->instance(MindicadorService::class, $doble);

        // Verificamos que el comando maneja el error con elegancia
        $this->artisan('indicadores:actualizar')
            ->expectsOutputToContain('✖ No fue posible consultar mindicador.cl')
            ->assertFailed();
    }
}