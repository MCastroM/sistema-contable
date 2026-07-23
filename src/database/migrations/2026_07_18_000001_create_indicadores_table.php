<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicadores', function (Blueprint $table) {
            $table->id();

            // Código del indicador: uf, utm, dolar, euro, ipc, tpm...
            $table->string('codigo', 20);

            // Nombre legible: "Unidad de Fomento", "Dólar observado"...
            $table->string('nombre', 100);

            // Unidad de medida según mindicador: "Pesos", "Porcentaje"...
            $table->string('unidad_medida', 30)->nullable();

            // Fecha a la que corresponde el valor (no la fecha de descarga)
            $table->date('fecha');

            // Valor del indicador.
            // decimal(12,4): la UF usa 2 decimales, el dólar 2, el IPC 1...
            // 4 decimales dan holgura sin usar float (¡nunca float para dinero!)
            $table->decimal('valor', 12, 4);

            $table->timestamps();

            // No puede existir dos veces el mismo indicador para la misma fecha
            $table->unique(['codigo', 'fecha']);

            // Búsquedas típicas: "dame la UF de hoy" → índice por código+fecha
            $table->index(['codigo', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicadores');
    }
};
