<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');            // año comercial: 2026

            // Cursor de protección parcial: nada anterior o igual a esta
            // fecha se puede crear/editar/anular. Null = sin bloqueo.
            $table->date('fecha_bloqueo')->nullable();

            // abierto | cerrado. El cierre es acción manual del contador.
            $table->string('estado', 10)->default('abierto');

            $table->timestamps();

            $table->unique(['empresa_id', 'anio']);          // un período por año y empresa
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
