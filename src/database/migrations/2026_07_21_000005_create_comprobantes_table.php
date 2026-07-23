<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();

            // I = Ingreso | E = Egreso | T = Traspaso
            $table->string('tipo', 1);

            // Correlativo por empresa + tipo + período (ver unique más abajo)
            $table->unsignedInteger('numero');

            $table->date('fecha');
            $table->string('glosa', 300);

            // borrador -> aprobado -> anulado
            // Aprobado jamás se edita: se anula o se revierte con otro comprobante.
            $table->string('estado', 10)->default('borrador');

            // Trazabilidad de quién lo creó y aprobó (tabla users de Breeze)
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('aprobado_at')->nullable();

            $table->timestamps();

            $table->unique(['empresa_id', 'tipo', 'periodo_id', 'numero']);
            $table->index(['empresa_id', 'fecha']);
            $table->index(['empresa_id', 'estado']);
        });

        DB::statement("ALTER TABLE comprobantes ADD CONSTRAINT chk_comprobantes_tipo
            CHECK (tipo IN ('I','E','T'))");
        DB::statement("ALTER TABLE comprobantes ADD CONSTRAINT chk_comprobantes_estado
            CHECK (estado IN ('borrador','aprobado','anulado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
