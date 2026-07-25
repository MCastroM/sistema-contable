<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoCompra extends Model
{
    protected $table = 'documentos_compra';

    protected $fillable = [
        'empresa_id', 'periodo_id', 'mes', 'nro', 'tipo_dte',
        'rut_proveedor', 'razon_social', 'folio', 'fecha',
        'exento', 'neto', 'iva', 'total', 'cuenta_gasto_id', 'comprobante_id',
    ];

    protected $casts = [
        'fecha'  => 'date',
        'exento' => 'decimal:2',
        'neto'   => 'decimal:2',
        'iva'    => 'decimal:2',
        'total'  => 'decimal:2',
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
