<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\MapeoCuenta;
use Illuminate\Support\Collection;

class ImportadorVentasService
{
    public const LIBRO = 'ventas';

    /**
     * Columnas esperadas: nro, tipo, rut, razon_social, folio, fecha,
     * exento, neto, iva, total. Igual que Compras, el libro de ventas
     * NO trae la cuenta de ingreso por documento -- la clasificacion
     * la hace el usuario la primera vez que aparece cada cliente
     * (mapeo por RUT), y queda memorizada para el futuro.
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
            'nro'          => (int) ($f['nro'] ?? 0),
            'tipo'         => trim((string) ($f['tipo'] ?? '')),
            'rut'          => trim((string) ($f['rut'] ?? '')),
            'razon_social' => trim((string) ($f['razon_social'] ?? '')),
            'folio'        => trim((string) ($f['folio'] ?? '')),
            'fecha'        => trim((string) ($f['fecha'] ?? '')),
            'exento'       => (float) ($f['exento'] ?? 0),
            'neto'         => (float) ($f['neto'] ?? 0),
            'iva'          => (float) ($f['iva'] ?? 0),
            'total'        => (float) ($f['total'] ?? 0),
        ])->filter(fn ($f) => $f['rut'] !== '');
    }

    /** Clientes (RUT + razón social) presentes en el archivo aún sin mapeo. */
    public function clientesSinMapeo(Empresa $empresa, Collection $filas): Collection
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
