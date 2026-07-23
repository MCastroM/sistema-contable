<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use App\Services\PlanCuentasService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Reconstruye el entorno de desarrollo completo:
     * usuario + empresa demo + período del año + plan de cuentas.
     * Uso: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $user = User::create([
            'name'     => 'Marco',
            'email'    => 'marco@test.cl',
            'password' => Hash::make('password123'),
        ]);

        // Autenticar: la bitácora exige autor en toda acción
        auth()->login($user);

        $empresa = Empresa::create([
            'rut'          => '76123456-7',
            'razon_social' => 'Empresa Demo SpA',
            'giro'         => 'Servicios contables',
        ]);

        $empresa->periodos()->create(['anio' => now()->year]);

        $total = app(PlanCuentasService::class)->instalarEn($empresa);

        $this->command->info("✔ Usuario: marco@test.cl / password123");
        $this->command->info("✔ Empresa: {$empresa->razon_social} + período " . now()->year);
        $this->command->info("✔ Plan de cuentas: {$total} cuentas instaladas");
    }
}