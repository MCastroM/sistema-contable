<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
    'rut', 'razon_social', 'giro', 'direccion', 'comuna', 'email', 'activa',
    'cuenta_proveedores_id', 'cuenta_iva_credito_id', 'cuenta_iva_debito_id',
    'cuenta_honorarios_pagar_id', 'cuenta_retencion_honorarios_id',
    'cuenta_remuneraciones_pagar_id', 'cuenta_deudores_ventas_id',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    public function periodos(): HasMany
    {
        return $this->hasMany(Periodo::class);
    }

    public function centrosCosto(): HasMany
    {
        return $this->hasMany(CentroCosto::class);
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class);
    }

    /** Período de un año dado (o null si no existe aún). */
    public function periodo(int $anio): ?Periodo
    {
        return $this->periodos()->where('anio', $anio)->first();
    }
}
