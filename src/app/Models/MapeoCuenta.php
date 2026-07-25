<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapeoCuenta extends Model
{
    protected $table = 'mapeo_cuentas_importacion';

    protected $fillable = ['empresa_id', 'libro', 'codigo_origen', 'nombre_origen', 'cuenta_id'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    /**
     * Busca (o prepara) el mapeo de un código de origen para un libro.
     * No lo crea automáticamente: eso lo decide el controlador de
     * importación, para no ensuciar la tabla con códigos que el
     * usuario aún no ha revisado.
     */
    public static function buscar(int $empresaId, string $libro, string $codigoOrigen): ?self
    {
        return static::where('empresa_id', $empresaId)
            ->where('libro', $libro)
            ->where('codigo_origen', $codigoOrigen)
            ->first();
    }
}
