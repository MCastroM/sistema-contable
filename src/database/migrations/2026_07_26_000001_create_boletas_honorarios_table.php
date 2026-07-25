<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletas_honorarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();

            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('nro');          // correlativo del libro

            $table->string('boleta', 30);                  // N° de boleta emitida por el prestador
            $table->date('fecha');
            $table->string('rut_prestador', 12);
            $table->string('nombre_prestador', 200);

            $table->decimal('brutos', 15, 2)->default(0);
            $table->decimal('retencion', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);    // líquido a pagar (brutos - retención)

            // Cuenta de gasto a la que se imputa (ej: Honorarios, Asesorías...)
            $table->foreignId('cuenta_gasto_id')->nullable()
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
        Schema::dropIfExists('boletas_honorarios');
    }
};
