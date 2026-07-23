<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Comprobante;
use App\Models\Cuenta;
use App\Models\Empresa;
use App\Models\Periodo;
use App\Models\User;
use App\Services\ComprobanteService;
use App\Services\PeriodoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NucleoContableTest extends TestCase
{
    use RefreshDatabase;   // migra una BD limpia para CADA test (usar BD de pruebas, ver phpunit.xml)

    private Empresa $empresa;
    private Periodo $periodo;
    private Cuenta $caja;
    private Cuenta $ventas;
    private Cuenta $iva;
    private Cuenta $agrupadora;
    private ComprobanteService $comprobantes;
    private PeriodoService $periodos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->empresa = Empresa::create([
            'rut' => '76000000-1', 'razon_social' => 'Test SpA',
        ]);
        $this->periodo = $this->empresa->periodos()->create(['anio' => 2026]);

        // Mini plan de cuentas para los tests
        $this->agrupadora = $this->empresa->cuentas()->create([
            'codigo' => '1.1', 'nombre' => 'Activo Corriente',
            'clase' => 'activo', 'imputable' => false,
        ]);
        $this->caja = $this->empresa->cuentas()->create([
            'codigo' => '1.1.01.001', 'nombre' => 'Caja',
            'clase' => 'activo', 'imputable' => true,
        ]);
        $this->ventas = $this->empresa->cuentas()->create([
            'codigo' => '4.1.01.001', 'nombre' => 'Ventas',
            'clase' => 'resultado', 'imputable' => true,
        ]);
        $this->iva = $this->empresa->cuentas()->create([
            'codigo' => '2.1.02.001', 'nombre' => 'IVA débito fiscal',
            'clase' => 'pasivo', 'imputable' => true,
        ]);

        $this->comprobantes = app(ComprobanteService::class);
        $this->periodos    = app(PeriodoService::class);
    }

    /** Una venta con IVA, cuadrada: neto 1.000.000 + IVA 190.000 */
    private function lineasVentaCuadrada(): array
    {
        return [
            ['cuenta_id' => $this->caja->id,   'debe' => 1190000, 'haber' => 0],
            ['cuenta_id' => $this->ventas->id, 'debe' => 0, 'haber' => 1000000],
            ['cuenta_id' => $this->iva->id,    'debe' => 0, 'haber' => 190000],
        ];
    }

    public function test_crea_borrador_con_correlativo_secuencial(): void
    {
        $c1 = $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-10', 'Venta 1', $this->lineasVentaCuadrada()
        );
        $c2 = $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-11', 'Venta 2', $this->lineasVentaCuadrada()
        );

        $this->assertSame(1, $c1->numero);
        $this->assertSame(2, $c2->numero);
        $this->assertSame('borrador', $c1->estado);
        $this->assertCount(3, $c1->movimientos);
    }

    public function test_aprueba_comprobante_cuadrado_y_registra_auditoria(): void
    {
        $c = $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-10', 'Venta del día', $this->lineasVentaCuadrada()
        );

        $aprobado = $this->comprobantes->aprobar($c);

        $this->assertSame('aprobado', $aprobado->estado);
        $this->assertNotNull($aprobado->aprobado_at);
        $this->assertSame(auth()->id(), $aprobado->aprobado_por);

        $this->assertDatabaseHas('auditorias', [
            'accion'      => 'comprobante.aprobar',
            'registro_id' => $c->id,
        ]);
    }

    public function test_rechaza_aprobar_comprobante_descuadrado(): void
    {
        $c = $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-10', 'Descuadrado', [
                ['cuenta_id' => $this->caja->id,   'debe' => 1190000, 'haber' => 0],
                ['cuenta_id' => $this->ventas->id, 'debe' => 0, 'haber' => 1000000],
                // faltan los 190.000 del IVA: debe != haber
            ]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/descuadrado/i');

        $this->comprobantes->aprobar($c);
    }

    public function test_rechaza_lineas_sobre_cuenta_no_imputable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->comprobantes->crearBorrador(
            $this->empresa, 'T', '2026-05-10', 'Sobre agrupadora', [
                ['cuenta_id' => $this->agrupadora->id, 'debe' => 1000, 'haber' => 0],
                ['cuenta_id' => $this->caja->id,       'debe' => 0, 'haber' => 1000],
            ]
        );
    }

    public function test_rechaza_cuenta_de_otra_empresa(): void
    {
        $otra = Empresa::create(['rut' => '77000000-2', 'razon_social' => 'Ajena Ltda']);
        $cuentaAjena = $otra->cuentas()->create([
            'codigo' => '1.1.01.001', 'nombre' => 'Caja ajena',
            'clase' => 'activo', 'imputable' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->comprobantes->crearBorrador(
            $this->empresa, 'T', '2026-05-10', 'Cruce de empresas', [
                ['cuenta_id' => $cuentaAjena->id, 'debe' => 1000, 'haber' => 0],
                ['cuenta_id' => $this->caja->id,  'debe' => 0, 'haber' => 1000],
            ]
        );
    }

    public function test_bloqueo_impide_crear_en_fechas_protegidas(): void
    {
        $this->periodos->bloquearHasta($this->periodo, '2026-06-30', 'F29 junio declarado');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/no es admitida/i');

        $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-10', 'En zona bloqueada', $this->lineasVentaCuadrada()
        );
    }

    public function test_anular_exige_motivo_y_deja_bitacora(): void
    {
        $c = $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-10', 'Para anular', $this->lineasVentaCuadrada()
        );
        $this->comprobantes->aprobar($c);

        $anulado = $this->comprobantes->anular($c, 'Factura emitida por error');

        $this->assertSame('anulado', $anulado->estado);
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'comprobante.anular',
            'motivo' => 'Factura emitida por error',
        ]);
    }

    public function test_no_cierra_periodo_con_borradores_pendientes(): void
    {
        $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-10', 'Quedó en borrador', $this->lineasVentaCuadrada()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/borrador/i');

        $this->periodos->cerrar($this->periodo);
    }

    public function test_reapertura_exige_motivo_y_queda_auditada(): void
    {
        $this->periodos->cerrar($this->periodo);

        // Sin motivo: rechazado
        try {
            $this->periodos->reabrir($this->periodo->refresh(), '  ');
            $this->fail('Debió exigir motivo.');
        } catch (\InvalidArgumentException) {
            // esperado
        }

        // Con motivo: reabre y audita
        $reabierto = $this->periodos->reabrir(
            $this->periodo->refresh(), 'Observación SII: ajuste depreciación'
        );

        $this->assertSame('abierto', $reabierto->estado);
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'periodo.reabrir',
            'motivo' => 'Observación SII: ajuste depreciación',
        ]);
    }

    public function test_comprobante_aprobado_no_se_puede_eliminar(): void
    {
        $c = $this->comprobantes->crearBorrador(
            $this->empresa, 'I', '2026-05-10', 'Aprobado intocable', $this->lineasVentaCuadrada()
        );
        $this->comprobantes->aprobar($c);

        $this->expectException(\RuntimeException::class);

        $this->comprobantes->eliminarBorrador($c);
    }
}
