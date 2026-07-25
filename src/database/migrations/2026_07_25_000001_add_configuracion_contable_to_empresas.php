<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Cuentas destino usadas por los servicios de centralización.
            // Nullable: la empresa puede operar sin estos libros auxiliares.
            $table->foreignId('cuenta_proveedores_id')->nullable()
                  ->after('activa')->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_credito_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_debito_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_honorarios_pagar_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_retencion_honorarios_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_remuneraciones_pagar_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_deudores_ventas_id')->nullable()
                  ->constrained('cuentas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cuenta_proveedores_id');
            $table->dropConstrainedForeignId('cuenta_iva_credito_id');
            $table->dropConstrainedForeignId('cuenta_iva_debito_id');
            $table->dropConstrainedForeignId('cuenta_honorarios_pagar_id');
            $table->dropConstrainedForeignId('cuenta_retencion_honorarios_id');
            $table->dropConstrainedForeignId('cuenta_remuneraciones_pagar_id');
            $table->dropConstrainedForeignId('cuenta_deudores_ventas_id');
        });
    }
};
