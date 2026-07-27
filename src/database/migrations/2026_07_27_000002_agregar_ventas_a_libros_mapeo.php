<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La restriccion original (chk_mapeo_libro) no incluia 'ventas'
     * como libro valido -- se agrego el modulo despues de esa
     * migracion original y se me olvido ampliarla.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE mapeo_cuentas_importacion DROP CONSTRAINT chk_mapeo_libro');
        DB::statement("ALTER TABLE mapeo_cuentas_importacion ADD CONSTRAINT chk_mapeo_libro
            CHECK (libro IN ('compras','honorarios','remuneraciones','diario','ventas'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE mapeo_cuentas_importacion DROP CONSTRAINT chk_mapeo_libro');
        DB::statement("ALTER TABLE mapeo_cuentas_importacion ADD CONSTRAINT chk_mapeo_libro
            CHECK (libro IN ('compras','honorarios','remuneraciones','diario'))");
    }
};
