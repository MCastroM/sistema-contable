<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();

            // cascadeOnDelete: si se elimina un comprobante BORRADOR,
            // sus líneas se van con él. (Los aprobados nunca se eliminan.)
            $table->foreignId('comprobante_id')->constrained('comprobantes')->cascadeOnDelete();

            $table->foreignId('cuenta_id')->constrained('cuentas')->restrictOnDelete();
            $table->foreignId('centro_costo_id')->nullable()
                  ->constrained('centros_costo')->restrictOnDelete();

            // Convención contable: columnas separadas, sin montos con signo.
            // decimal(15,2): hasta 9.999.999.999.999,99 — holgado para CLP.
            $table->decimal('debe', 15, 2)->default(0);
            $table->decimal('haber', 15, 2)->default(0);

            $table->string('glosa', 300)->nullable();       // glosa por línea (opcional)
            $table->timestamps();

            $table->index('cuenta_id');
            $table->index('centro_costo_id');
        });

        // Blindaje a nivel de base de datos:
        // - montos no negativos
        // - cada línea usa debe O haber, nunca ambos, nunca ninguno
        DB::statement("ALTER TABLE movimientos ADD CONSTRAINT chk_mov_no_negativos
            CHECK (debe >= 0 AND haber >= 0)");
        DB::statement("ALTER TABLE movimientos ADD CONSTRAINT chk_mov_debe_xor_haber
            CHECK ( (debe > 0 AND haber = 0) OR (haber > 0 AND debe = 0) )");

        // Nota: la regla suma(debe) = suma(haber) POR COMPROBANTE se valida
        // en el servicio de aprobación (no se puede expresar como CHECK simple
        // porque cruza filas). Se reforzará con lógica transaccional al aprobar.
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
