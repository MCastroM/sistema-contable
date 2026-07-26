<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas del libro de ABSAL como campos propios (fidelidad para
     * la reconstrucción histórica e impresión). El campo `otros` (JSON)
     * queda disponible para clientes con columnas adicionales distintas,
     * sin tener que migrar la tabla cada vez.
     */
    public function up(): void
    {
        Schema::create('remuneraciones_trabajador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();

            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('nro');      // correlativo del libro (orden de fila)

            $table->string('rut_trabajador', 12);
            $table->string('nombre_trabajador', 200);

            // ── Haberes ──
            $table->decimal('sueldo', 15, 2)->default(0);
            $table->decimal('gratificacion', 15, 2)->default(0);
            $table->decimal('movilizacion', 15, 2)->default(0);
            $table->decimal('colacion', 15, 2)->default(0);
            $table->decimal('otros_haberes', 15, 2)->default(0);
            $table->decimal('produccion', 15, 2)->default(0);
            $table->decimal('total_haberes', 15, 2)->default(0);

            // ── Descuentos ──
            $table->decimal('afp', 15, 2)->default(0);
            $table->decimal('salud', 15, 2)->default(0);
            $table->decimal('pactado_salud', 15, 2)->default(0); // "PACTADO" del Excel (salud pactada UF)
            $table->decimal('cesantia', 15, 2)->default(0);
            $table->decimal('impuesto_unico', 15, 2)->default(0);
            $table->decimal('prestamo', 15, 2)->default(0);
            $table->decimal('cuenta_ahorro', 15, 2)->default(0);
            $table->decimal('anticipo', 15, 2)->default(0);

            $table->decimal('liquido', 15, 2)->default(0);   // líquido a pagar

            // Columnas específicas de un cliente que no calzan en las de arriba
            $table->jsonb('otros')->nullable();

            $table->foreignId('comprobante_id')->nullable()
                  ->constrained('comprobantes')->nullOnDelete();

            $table->timestamps();

            $table->unique(['empresa_id', 'periodo_id', 'mes', 'nro']);
            $table->index(['empresa_id', 'periodo_id', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remuneraciones_trabajador');
    }
};
