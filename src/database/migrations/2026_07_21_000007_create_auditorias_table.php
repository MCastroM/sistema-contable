<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            // Quién (siempre obligatorio: no hay acciones anónimas)
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Sobre qué empresa (nullable: hay acciones globales, ej. crear empresa)
            $table->foreignId('empresa_id')->nullable()
                  ->constrained('empresas')->restrictOnDelete();

            // Qué hizo: 'periodo.cerrar', 'periodo.reabrir',
            // 'comprobante.aprobar', 'comprobante.anular', etc.
            $table->string('accion', 50);

            // Sobre qué registro: tabla + id (referencia genérica)
            $table->string('tabla', 50)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();

            // Por qué: obligatorio en acciones sensibles (reapertura, anulación).
            $table->text('motivo')->nullable();

            // Fotografía de los datos relevantes antes/después (JSON)
            $table->jsonb('cambios')->nullable();

            // Desde dónde
            $table->string('ip', 45)->nullable();

            // Cuándo: solo created_at; una auditoría jamás se actualiza
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa_id', 'accion']);
            $table->index(['tabla', 'registro_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
