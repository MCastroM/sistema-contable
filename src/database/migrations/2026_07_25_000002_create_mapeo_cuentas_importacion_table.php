<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Traduce el código de cuenta que usaba el contador en su Excel
     * al id de la cuenta real de nuestro plan. Se arma UNA vez por
     * empresa (y por libro, porque compras/honorarios/remuneraciones/
     * diario pueden traer códigos distintos) y queda reutilizable:
     * el próximo mes del mismo cliente ya no requiere volver a mapear.
     */
    public function up(): void
    {
        Schema::create('mapeo_cuentas_importacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            // De qué libro viene: compras | honorarios | remuneraciones | diario
            $table->string('libro', 20);

            $table->string('codigo_origen', 30);   // código tal como venía en el Excel del contador
            $table->string('nombre_origen', 150)->nullable(); // nombre que traía, solo referencia

            // A qué cuenta REAL de nuestro plan corresponde.
            // Nullable mientras el usuario no lo haya resuelto todavía.
            $table->foreignId('cuenta_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();

            $table->timestamps();

            $table->unique(['empresa_id', 'libro', 'codigo_origen']);
        });

        DB::statement("ALTER TABLE mapeo_cuentas_importacion ADD CONSTRAINT chk_mapeo_libro
            CHECK (libro IN ('compras','honorarios','remuneraciones','diario'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('mapeo_cuentas_importacion');
    }
};
