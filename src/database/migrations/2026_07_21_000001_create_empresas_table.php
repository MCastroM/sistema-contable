<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('rut', 12)->unique();      // formato: 76123456-7
            $table->string('razon_social', 200);
            $table->string('giro', 200)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('comuna', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
