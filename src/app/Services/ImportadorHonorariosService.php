<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\MapeoCuenta;
use Illuminate\Support\Collection;

class ImportadorHonorariosService
{
    public const LIBRO = 'honorarios';

    /**
     * Columnas esperadas: nro, boleta, fecha, rut, nombre, brutos, retencion, total
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

    private function normalizar(Collection $filas): Collection
    {
        return $filas->map(fn ($f) => [
            'nro'      => (int) ($f['nro'] ?? 0),
            'boleta'   => trim((string) ($f['boleta'] ?? '')),
            'fecha'    => trim((string) ($f['fecha'] ?? '')),
            'rut'      => trim((string) ($f['rut'] ?? '')),
            'nombre'   => trim((string) ($f['nombre'] ?? '')),
            'brutos'   => (float) ($f['brutos'] ?? 0),
            'retencion'=> (float) ($f['retencion'] ?? 0),
            'total'    => (float) ($f['total'] ?? 0),
        ])->filter(fn ($f) => $f['rut'] !== '');
    }

    /** Prestadores (RUT + nombre) del archivo aún sin cuenta de gasto asignada. */
    public function prestadoresSinMapeo(Empresa $empresa, Collection $filas): Collection
    {
        $mapeados = MapeoCuenta::where('empresa_id', $empresa->id)
            ->where('libro', self::LIBRO)
            ->pluck('codigo_origen')
            ->all();

        return $filas
            ->unique('rut')
            ->reject(fn ($f) => in_array($f['rut'], $mapeados, true))
            ->values();
    }
}
