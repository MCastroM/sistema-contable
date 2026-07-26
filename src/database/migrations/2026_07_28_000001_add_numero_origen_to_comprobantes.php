<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El "N° COMP" que traía el libro original de ABSAL no es el mismo
     * correlativo que asigna nuestro ComprobanteService (que numera por
     * tipo I/E/T). Guardamos el número original como referencia, para
     * poder trazar cada comprobante importado hasta su fila de origen
     * en el Excel — útil para auditoría y para el módulo de impresión.
     */
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('numero_origen', 30)->nullable()->after('numero');
            $table->index('numero_origen');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('numero_origen');
        });
    }
};
