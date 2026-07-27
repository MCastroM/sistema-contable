<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * codigo_origen era varchar(30) -- algunos nombres de cuenta del
     * libro historico (ej: "NOM:RESERVA REVALORIZACION CAPITAL", 34
     * caracteres) superan ese limite. Se amplia a 150 para tener
     * margen holgado con nombres largos futuros.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE mapeo_cuentas_importacion ALTER COLUMN codigo_origen TYPE VARCHAR(150)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE mapeo_cuentas_importacion ALTER COLUMN codigo_origen TYPE VARCHAR(30)');
    }
};
