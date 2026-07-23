<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicador extends Model
{
    // Laravel pluralizaría "indicadors"; le indicamos la tabla correcta
    protected $table = 'indicadores';

    protected $fillable = [
        'codigo',
        'nombre',
        'unidad_medida',
        'fecha',
        'valor',
    ];

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:4',
    ];

    /**
     * Último valor disponible de un indicador.
     * Uso: Indicador::ultimo('uf')?->valor
     */
    public static function ultimo(string $codigo): ?self
    {
        return static::where('codigo', $codigo)
            ->orderByDesc('fecha')
            ->first();
    }

    /**
     * Valor de un indicador para una fecha específica (o el último
     * disponible ANTES de esa fecha — útil para fines de semana/feriados,
     * donde p.ej. el dólar no tiene valor publicado).
     * Uso: Indicador::valorAlDia('dolar', '2026-07-15')
     */
    public static function valorAlDia(string $codigo, string $fecha): ?self
    {
        return static::where('codigo', $codigo)
            ->whereDate('fecha', '<=', $fecha)
            ->orderByDesc('fecha')
            ->first();
    }
}
