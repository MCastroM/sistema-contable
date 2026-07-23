<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    // La bitácora solo se inserta: no hay updated_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'empresa_id', 'accion', 'tabla', 'registro_id',
        'motivo', 'cambios', 'ip',
    ];

    protected $casts = [
        'cambios' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Helper para registrar una acción en un solo llamado.
     * Uso:
     *   Auditoria::registrar('periodo.reabrir', $periodo, motivo: $motivo,
     *       cambios: ['estado' => ['cerrado', 'abierto']]);
     */
    public static function registrar(
        string $accion,
        ?Model $registro = null,
        ?string $motivo = null,
        ?array $cambios = null,
        ?int $empresaId = null,
    ): self {
        return static::create([
            'user_id'     => auth()->id(),
            'empresa_id'  => $empresaId ?? $registro?->empresa_id ?? null,
            'accion'      => $accion,
            'tabla'       => $registro?->getTable(),
            'registro_id' => $registro?->getKey(),
            'motivo'      => $motivo,
            'cambios'     => $cambios,
            'ip'          => request()?->ip(),
        ]);
    }
}
