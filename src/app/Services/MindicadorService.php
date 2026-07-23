<?php

namespace App\Services;

use App\Models\Indicador;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MindicadorService
{
    private const BASE_URL = 'https://mindicador.cl/api';

    /**
     * Indicadores que nos interesan para el sistema contable.
     * (mindicador ofrece más: euro, ivp, imacec, tasa_desempleo, bitcoin...)
     */
    public const INDICADORES = ['uf', 'utm', 'dolar', 'ipc', 'tpm'];

    /**
     * Descarga los valores del día desde mindicador.cl y los guarda.
     * Devuelve un resumen de lo actualizado.
     */
    public function actualizarDiarios(): array
    {
        $resumen = [];

        // Una sola llamada al endpoint raíz trae el valor vigente
        // de TODOS los indicadores → más eficiente que uno por uno.
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'SistemaContable/1.0'])
            ->retry(3, 2000)          // 3 intentos, 2s entre cada uno
            ->get(self::BASE_URL);

        if ($response->failed()) {
            Log::error('mindicador.cl no respondió', ['status' => $response->status()]);
            throw new \RuntimeException('No fue posible consultar mindicador.cl');
        }

        $data = $response->json();

        foreach (self::INDICADORES as $codigo) {
            if (!isset($data[$codigo])) {
                $resumen[$codigo] = 'no disponible en la respuesta';
                continue;
            }

            $item = $data[$codigo];

            // updateOrCreate: si ya existe ese código+fecha lo actualiza,
            // si no, lo crea → el comando se puede correr N veces sin duplicar
            $indicador = Indicador::updateOrCreate(
                [
                    'codigo' => $codigo,
                    'fecha'  => substr($item['fecha'], 0, 10), // "2026-07-18T04:00:00.000Z" → "2026-07-18"
                ],
                [
                    'nombre'        => $item['nombre'],
                    'unidad_medida' => $item['unidad_medida'] ?? null,
                    'valor'         => $item['valor'],
                ]
            );

            $resumen[$codigo] = sprintf(
                '%s (%s) = %s',
                $indicador->nombre,
                $indicador->fecha->format('d-m-Y'),
                number_format((float) $indicador->valor, 2, ',', '.')
            );
        }

        return $resumen;
    }

    /**
     * Descarga el histórico de un indicador para un año completo.
     * Útil para poblar datos pasados (ej: UF de todo 2025 para reliquidaciones).
     */
    public function importarAnio(string $codigo, int $anio): int
    {
        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'SistemaContable/1.0'])
            ->retry(3, 2000)
            ->get(self::BASE_URL . "/{$codigo}/{$anio}");

        if ($response->failed()) {
            throw new \RuntimeException("No fue posible obtener {$codigo} del año {$anio}");
        }

        $data = $response->json();
        $importados = 0;

        foreach ($data['serie'] ?? [] as $item) {
            Indicador::updateOrCreate(
                [
                    'codigo' => $codigo,
                    'fecha'  => substr($item['fecha'], 0, 10),
                ],
                [
                    'nombre'        => $data['nombre'] ?? $codigo,
                    'unidad_medida' => $data['unidad_medida'] ?? null,
                    'valor'         => $item['valor'],
                ]
            );
            $importados++;
        }

        return $importados;
    }
}
