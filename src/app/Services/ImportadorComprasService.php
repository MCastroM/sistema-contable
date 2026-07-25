<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\MapeoCuenta;
use Illuminate\Support\Collection;

class ImportadorComprasService
{
    public const LIBRO = 'compras';

    /**
     * Formato esperado del archivo (CSV o la hoja exportada de Excel),
     * con encabezado en la primera fila, columnas en este orden:
     *   nro, tipo, rut, razon_social, folio, fecha (AAAA-MM-DD), exento, neto, iva, total
     *
     * Para migrar un libro de compras de un Excel más elaborado (como el
     * de ABSAL, con varios meses en una sola hoja), primero se copia el
     * bloque del mes a una hoja/CSV aparte con estas columnas.
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
        // Requiere: composer require phpoffice/phpspreadsheet
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
                continue; // fila vacía
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

    /**
     * Proveedores (RUT + razón social) presentes en el archivo que
     * AÚN no tienen mapeo a una cuenta de gasto para esta empresa.
     */
    public function proveedoresSinMapeo(Empresa $empresa, Collection $filas): Collection
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
