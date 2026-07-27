<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoVenta extends Model
{
    protected $table = 'documentos_venta';

    protected $fillable = [
        'empresa_id', 'periodo_id', 'mes', 'nro', 'tipo_dte',
        'rut_cliente', 'razon_social', 'folio', 'fecha',
        'exento', 'neto', 'iva', 'total', 'cuenta_ingreso_id', 'comprobante_id',
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

    public function cuentaIngreso(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_ingreso_id');
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
