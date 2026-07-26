<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    protected $table = 'comprobantes';

    public const TIPO_INGRESO  = 'I';
    public const TIPO_EGRESO   = 'E';
    public const TIPO_TRASPASO = 'T';

    public const BORRADOR = 'borrador';
    public const APROBADO = 'aprobado';
    public const ANULADO  = 'anulado';

    protected $fillable = [
        'empresa_id', 'periodo_id', 'tipo', 'numero', 'numero_origen','fecha', 'glosa',
        'estado', 'creado_por', 'aprobado_por', 'aprobado_at',
    ];

    protected $casts = [
        'numero'      => 'integer',
        'fecha'       => 'date',
        'aprobado_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function esBorrador(): bool
    {
        return $this->estado === self::BORRADOR;
    }

    public function totalDebe(): string
    {
        return (string) $this->movimientos()->sum('debe');
    }

    public function totalHaber(): string
    {
        return (string) $this->movimientos()->sum('haber');
    }

    /**
     * La regla sagrada de la partida doble.
     * bccomp compara decimales como texto con precisión exacta
     * (extensión bcmath): 0 = iguales. Nunca comparar floats con ==.
     */
    public function estaCuadrado(): bool
    {
        return bccomp($this->totalDebe(), $this->totalHaber(), 2) === 0
            && $this->movimientos()->count() >= 2;
    }

    /** Correlativo formateado para mostrar: I-2026-000123 */
    public function folio(): string
    {
        return sprintf('%s-%d-%06d', $this->tipo, $this->periodo->anio, $this->numero);
    }
}
