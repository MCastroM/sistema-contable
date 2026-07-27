<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();

            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('nro');          // correlativo del libro

            $table->string('tipo_dte', 5);                 // 33 factura afecta, 34 exenta, 61 nota credito, etc.
            $table->string('rut_cliente', 12);
            $table->string('razon_social', 200);
            $table->string('folio', 30)->nullable();       // folio del documento emitido
            $table->date('fecha');

            $table->decimal('exento', 15, 2)->default(0);
            $table->decimal('neto', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Cuenta de ingreso a la que se imputa este documento al
            // centralizar (ej: Ventas, Ingresos por servicios...).
            $table->foreignId('cuenta_ingreso_id')->nullable()
                  ->constrained('cuentas')->restrictOnDelete();

            $table->foreignId('comprobante_id')->nullable()
                  ->constrained('comprobantes')->nullOnDelete();

            $table->timestamps();

            $table->unique(['empresa_id', 'periodo_id', 'mes', 'nro']);
            $table->index(['empresa_id', 'periodo_id', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_venta');
    }
};
