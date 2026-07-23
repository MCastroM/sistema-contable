<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();

            // Jerarquía: null = cuenta de primer nivel (clase).
            // restrictOnDelete: no se puede borrar una cuenta con hijas.
            $table->foreignId('padre_id')->nullable()
                  ->constrained('cuentas')->restrictOnDelete();

            $table->string('codigo', 20);                    // ej: 1.1.01.001
            $table->string('nombre', 150);

            // activo | pasivo | patrimonio | resultado
            $table->string('clase', 12);

            // Solo las cuentas imputables (hojas) reciben movimientos;
            // las agrupadoras solo consolidan a sus hijas.
            $table->boolean('imputable')->default(false);

            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
            $table->index(['empresa_id', 'clase']);
        });

        // Refuerzo a nivel de base: clase solo admite los 4 valores válidos
        DB::statement("ALTER TABLE cuentas ADD CONSTRAINT chk_cuentas_clase
            CHECK (clase IN ('activo','pasivo','patrimonio','resultado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
