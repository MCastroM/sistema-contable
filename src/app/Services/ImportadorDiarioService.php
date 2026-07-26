<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\MapeoCuenta;
use Illuminate\Support\Collection;

class ImportadorDiarioService
{
    public const LIBRO = 'diario';

    /**
     * Columnas esperadas: cod, fecha, comprobante, glosa, cc, debe, haber
     * (cheque es opcional/ignorado). "cod" es el código de cuenta TAL
     * COMO lo traía el contador — se traduce con el mapeo, igual que
     * hicimos con RUT en Compras/Honorarios, pero aquí la clave es el
     * código de cuenta en vez de un RUT.
     *
     * "comprobante" es el N° original del libro: varias filas con el
     * mismo número forman UN asiento (varias líneas, un comprobante).
     */
    public function leerFilas(string $rutaAbsoluta): Collection
    {
        $extension = strtolower(pathinfo($rutaAbsoluta, PATHINFO_EXTENSION));

        return $extension === 'csv'
            ? $this->leerCsv($rutaAbsoluta)
            : $this->leerXlsx($rutaAbsoluta);
    }

    private function leerCsv(string $ruta): Collection
    {
        $filas = collect();
        $handle = fopen($ruta, 'r');
        $encabezado = null;

        while (($linea = fgetcsv($handle)) !== false) {
            if ($encabezado === null) {
                $encabezado = array_map('strtolower', $linea);
                continue;
            }
            $filas->push(array_combine($encabezado, $linea));
        }
        fclose($handle);

        return $this->normalizar($filas);
    }

    private function leerXlsx(string $ruta): Collection
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($ruta);
        $hoja = $spreadsheet->getActiveSheet();
        $filas = collect();
        $encabezado = null;

        foreach ($hoja->toArray(null, true, true, false) as $fila) {
            if ($encabezado === null) {
                $encabezado = array_map(fn ($c) => strtolower(trim((string) $c)), $fila);
                continue;
            }
            if (! array_filter($fila, fn ($c) => trim((string) $c) !== '')) {
                continue;
            }
            $filas->push(array_combine($encabezado, $fila));
        }

        return $this->normalizar($filas);
    }

    /**
     * Parser de fecha robusto: acepta separadores de coma, punto o
     * guion (el Excel de ABSAL trae los tres mezclados). Devuelve
     * 'Y-m-d' o null si la fila no es interpretable — se reporta al
     * usuario, no se descarta en silencio.
     */
    public function parsearFecha(?string $s): ?string
    {
        if ($s === null || trim($s) === '') {
            return null;
        }
        $s = trim($s);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s;
        }

        if (preg_match('/^(\d{1,2})[.,\-](\d{1,2})[.,\-](\d{4,5})$/', $s, $m)) {
            $dia = (int) $m[1];
            $mes = (int) $m[2];
            $anio = (int) substr($m[3], 0, 4);   // corta años de 5 dígitos mal tipeados
            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }

        return null;
    }

    private function normalizar(Collection $filas): Collection
    {
        return $filas->map(fn ($f) => [
            'cod'         => trim((string) ($f['cod'] ?? '')),
            'fecha_raw'   => trim((string) ($f['fecha'] ?? '')),
            'fecha'       => $this->parsearFecha($f['fecha'] ?? null),
            'comprobante' => trim((string) ($f['comprobante'] ?? '')),
            'glosa'       => trim((string) ($f['glosa'] ?? '')),
            'cc'          => trim((string) ($f['cc'] ?? '')),
            'debe'        => (float) ($f['debe'] ?? 0),
            'haber'       => (float) ($f['haber'] ?? 0),
        ])->filter(fn ($f) => $f['cod'] !== '' && $f['comprobante'] !== '');
    }

    /** Códigos de cuenta del archivo aún sin mapeo para esta empresa. */
    public function codigosSinMapeo(Empresa $empresa, Collection $filas): Collection
    {
        $mapeados = MapeoCuenta::where('empresa_id', $empresa->id)
            ->where('libro', self::LIBRO)
            ->pluck('codigo_origen')
            ->all();

        return $filas->pluck('cod')->unique()
            ->reject(fn ($c) => in_array($c, $mapeados, true))
            ->values();
    }

    /** Agrupa las filas por N° de comprobante original (un asiento = N líneas). */
    public function agruparPorComprobante(Collection $filas): Collection
    {
        return $filas->groupBy('comprobante');
    }
}
