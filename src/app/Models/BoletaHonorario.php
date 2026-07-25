<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletaHonorario extends Model
{
    protected $table = 'boletas_honorarios';

    protected $fillable = [
        'empresa_id', 'periodo_id', 'mes', 'nro', 'boleta', 'fecha',
        'rut_prestador', 'nombre_prestador', 'brutos', 'retencion', 'total',
        'cuenta_gasto_id', 'comprobante_id',
    ];

    protected $casts = [
        'fecha'     => 'date',
        'brutos'    => 'decimal:2',
        'retencion' => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function cuentaGasto(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_gasto_id');
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function estaCentralizado(): bool
    {
        return $this->comprobante_id !== null;
    }
}
