<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemuneracionTrabajador extends Model
{
    protected $table = 'remuneraciones_trabajador';

    protected $fillable = [
        'empresa_id', 'periodo_id', 'mes', 'nro', 'rut_trabajador', 'nombre_trabajador',
        'sueldo', 'gratificacion', 'movilizacion', 'colacion', 'otros_haberes', 'produccion', 'total_haberes',
        'afp', 'salud', 'pactado_salud', 'cesantia', 'impuesto_unico', 'prestamo', 'cuenta_ahorro', 'anticipo',
        'liquido', 'otros', 'comprobante_id',
    ];

    protected $casts = [
        'sueldo' => 'decimal:2', 'gratificacion' => 'decimal:2', 'movilizacion' => 'decimal:2',
        'colacion' => 'decimal:2', 'otros_haberes' => 'decimal:2', 'produccion' => 'decimal:2',
        'total_haberes' => 'decimal:2', 'afp' => 'decimal:2', 'salud' => 'decimal:2',
        'pactado_salud' => 'decimal:2', 'cesantia' => 'decimal:2', 'impuesto_unico' => 'decimal:2',
        'prestamo' => 'decimal:2', 'cuenta_ahorro' => 'decimal:2', 'anticipo' => 'decimal:2',
        'liquido' => 'decimal:2', 'otros' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function estaCentralizado(): bool
    {
        return $this->comprobante_id !== null;
    }

    /** Suma de descuentos previsionales/legales (AFP + Salud + Pactado + Cesantía). */
    public function totalPrevisional(): string
    {
        return bcadd(bcadd((string) $this->afp, (string) $this->salud, 2),
                      bcadd((string) $this->pactado_salud, (string) $this->cesantia, 2), 2);
    }
}
