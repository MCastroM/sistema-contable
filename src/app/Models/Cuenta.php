<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    protected $table = 'cuentas';

    public const CLASES = ['activo', 'pasivo', 'patrimonio', 'resultado'];

    protected $fillable = [
        'empresa_id', 'padre_id', 'codigo', 'nombre', 'clase', 'imputable', 'activa',
    ];

    protected $casts = [
        'imputable' => 'boolean',
        'activa'    => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'padre_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(Cuenta::class, 'padre_id')->orderBy('codigo');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    /** Solo cuentas que pueden recibir movimientos. */
    public function scopeImputables($query)
    {
        return $query->where('imputable', true)->where('activa', true);
    }

    /** Nivel de profundidad según el código: 1.1.01.001 => 4 */
    public function nivel(): int
    {
        return substr_count($this->codigo, '.') + 1;
    }
}
