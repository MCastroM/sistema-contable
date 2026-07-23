<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periodo extends Model
{
    protected $table = 'periodos';

    public const ABIERTO = 'abierto';
    public const CERRADO = 'cerrado';

    protected $fillable = [
        'empresa_id', 'anio', 'fecha_bloqueo', 'estado',
    ];

    protected $casts = [
        'anio'          => 'integer',
        'fecha_bloqueo' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class);
    }

    public function estaCerrado(): bool
    {
        return $this->estado === self::CERRADO;
    }

    /**
     * ¿Se puede registrar/modificar movimiento con esta fecha?
     * Regla: período abierto Y fecha posterior al bloqueo (si existe).
     * El cierre y la reapertura viven en un servicio (con auditoría),
     * no aquí: el modelo solo responde preguntas.
     */
    public function admiteFecha(\DateTimeInterface|string $fecha): bool
    {
        if ($this->estaCerrado()) {
            return false;
        }

        $fecha = $fecha instanceof \DateTimeInterface
            ? \Carbon\Carbon::instance($fecha)
            : \Carbon\Carbon::parse($fecha);

        if ($fecha->year !== $this->anio) {
            return false;
        }

        if ($this->fecha_bloqueo !== null && $fecha->lte($this->fecha_bloqueo)) {
            return false;
        }

        return true;
    }
}
