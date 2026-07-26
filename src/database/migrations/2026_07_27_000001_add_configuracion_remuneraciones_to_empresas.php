<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ya existían (desde el módulo de Compras): cuenta_remuneraciones_pagar_id.
     * Estas 4 son nuevas, específicas de remuneraciones.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('cuenta_impuesto_unico_id')->nullable()
                  ->after('cuenta_remuneraciones_pagar_id')->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_leyes_sociales_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_anticipo_sueldo_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_prestamo_personal_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_ahorro_trabajador_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cuenta_impuesto_unico_id');
            $table->dropConstrainedForeignId('cuenta_leyes_sociales_id');
            $table->dropConstrainedForeignId('cuenta_anticipo_sueldo_id');
            $table->dropConstrainedForeignId('cuenta_prestamo_personal_id');
            $table->dropConstrainedForeignId('cuenta_ahorro_trabajador_id');
        });
    }
};
