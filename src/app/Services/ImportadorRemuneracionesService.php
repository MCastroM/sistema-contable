<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ImportadorRemuneracionesService
{
    /**
     * Columnas esperadas (CSV/Excel), en este orden:
     *   nro, rut, nombre, sueldo, gratificacion, movilizacion, colacion,
     *   otros_haberes, produccion, total_haberes, afp, salud, pactado_salud,
     *   cesantia, impuesto_unico, prestamo, cuenta_ahorro, anticipo, liquido
     *
     * A diferencia de Compras/Honorarios, Remuneraciones NO requiere
     * mapeo de cuentas por trabajador: todos centralizan a las mismas
     * cuentas fijas configuradas en la empresa (Remuneraciones, Impuesto
     * Único, Leyes Sociales, etc.) — no hay clasificación por persona.
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
        $campos = ['sueldo', 'gratificacion', 'movilizacion', 'colacion', 'otros_haberes',
                   'produccion', 'total_haberes', 'afp', 'salud', 'pactado_salud', 'cesantia',
                   'impuesto_unico', 'prestamo', 'cuenta_ahorro', 'anticipo', 'liquido'];

        return $filas->map(function ($f) use ($campos) {
            $fila = [
                'nro'    => (int) ($f['nro'] ?? 0),
                'rut'    => trim((string) ($f['rut'] ?? '')),
                'nombre' => trim((string) ($f['nombre'] ?? '')),
            ];
            foreach ($campos as $c) {
                $fila[$c] = (float) ($f[$c] ?? 0);
            }

            return $fila;
        })->filter(fn ($f) => $f['rut'] !== '');
    }
}
