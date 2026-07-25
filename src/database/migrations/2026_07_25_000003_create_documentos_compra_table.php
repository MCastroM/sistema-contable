<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();

            $table->unsignedTinyInteger('mes');           // 1-12: el mes del libro (no siempre = mes de la fecha del doc)
            $table->unsignedSmallInteger('nro');          // correlativo del libro tal como venía en el Excel

            // Tipo de documento SII: 33 factura afecta, 34 factura exenta,
            // 61 nota de crédito, etc. Guardamos el código tal cual venía.
            $table->string('tipo_dte', 5);

            $table->string('rut_proveedor', 12);
            $table->string('razon_social', 200);
            $table->string('folio', 30)->nullable();      // folio del documento del proveedor
            $table->date('fecha');

            $table->decimal('exento', 15, 2)->default(0);
            $table->decimal('neto', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Cuenta de gasto/activo a la que se imputa este documento
            // al centralizar (ej: Arriendos, Materiales de oficina...).
            // Nullable: puede quedar pendiente de clasificar.
            $table->foreignId('cuenta_gasto_id')->nullable()
                  ->constrained('cuentas')->restrictOnDelete();

            // Si ya fue centralizado, aquí queda el comprobante generado.
            // Null = pendiente de centralizar.
            $table->foreignId('comprobante_id')->nullable()
                  ->constrained('comprobantes')->nullOnDelete();

            $table->timestamps();

            $table->unique(['empresa_id', 'periodo_id', 'mes', 'nro']);
            $table->index(['empresa_id', 'periodo_id', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_compra');
    }
};
