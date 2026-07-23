<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mayor de cuenta
            </h2>
            <a href="{{ route('reportes.libro-mayor', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}"
               class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 print:hidden">
                ← Volver al resumen
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filtros --}}
            <form method="GET" action="{{ route('reportes.libro-mayor.cuenta', $cuenta) }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 print:hidden">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Desde</label>
                        <input type="date" name="desde" value="{{ $desde->toDateString() }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
                        <input type="date" name="hasta" value="{{ $hasta->toDateString() }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div class="flex gap-2 md:col-span-2">
                        <button type="submit"
                                class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                            Filtrar
                        </button>
                        <button type="button" onclick="window.print()"
                                class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                            Imprimir
                        </button>
                    </div>
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">

                <div class="text-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        <span class="font-mono">{{ $cuenta->codigo }}</span> — {{ $cuenta->nombre }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $empresa->razon_social }} — RUT {{ $empresa->rut }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Del {{ $desde->format('d-m-Y') }} al {{ $hasta->format('d-m-Y') }}
                        · Cuenta {{ $detalle['esDeudora'] ? 'deudora' : 'acreedora' }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-2 py-2 text-left">Fecha</th>
                                <th class="px-2 py-2 text-left">Folio</th>
                                <th class="px-2 py-2 text-left">Glosa</th>
                                <th class="px-2 py-2 text-right">Debe</th>
                                <th class="px-2 py-2 text-right">Haber</th>
                                <th class="px-2 py-2 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                            {{-- Saldo de arrastre --}}
                            <tr class="bg-gray-50 dark:bg-gray-700/40 text-gray-600 dark:text-gray-300">
                                <td colspan="5" class="px-2 py-2 text-right italic text-xs">
                                    Saldo anterior al {{ $desde->format('d-m-Y') }}
                                </td>
                                <td class="px-2 py-2 text-right font-mono font-semibold">
                                    ${{ number_format((float) $detalle['saldoAnterior'], 0, ',', '.') }}
                                </td>
                            </tr>

                            @forelse ($detalle['lineas'] as $linea)
                                @php $m = $linea->movimiento; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                        {{ $m->comprobante->fecha->format('d-m-Y') }}
                                    </td>
                                    <td class="px-2 py-2 font-mono text-xs whitespace-nowrap">
                                        <a href="{{ route('comprobantes.show', $m->comprobante) }}"
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                            {{ $m->comprobante->folio() }}
                                        </a>
                                    </td>
                                    <td class="px-2 py-2 text-gray-800 dark:text-gray-200">
                                        {{ $m->glosa ?: $m->comprobante->glosa }}
                                        @if ($m->centroCosto)
                                            <span class="text-xs text-gray-400 ml-1">[CC: {{ $m->centroCosto->codigo }}]</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono">
                                        {{ bccomp($m->debe, '0', 2) === 1 ? '$' . number_format((float) $m->debe, 0, ',', '.') : '' }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono">
                                        {{ bccomp($m->haber, '0', 2) === 1 ? '$' . number_format((float) $m->haber, 0, ',', '.') : '' }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono font-medium
                                               {{ bccomp($linea->saldo, '0', 2) === -1 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100' }}">
                                        ${{ number_format((float) $linea->saldo, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-2 py-10 text-center text-gray-400">
                                        Sin movimientos aprobados en el período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                            <tr>
                                <td colspan="3" class="px-2 py-3 text-gray-900 dark:text-gray-100">
                                    TOTALES DEL PERÍODO
                                    <span class="font-normal text-xs text-gray-500 ml-2">
                                        ({{ count($detalle['lineas']) }} movimientos)
                                    </span>
                                </td>
                                <td class="px-2 py-3 text-right font-mono">
                                    ${{ number_format((float) $detalle['totalDebe'], 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-3 text-right font-mono">
                                    ${{ number_format((float) $detalle['totalHaber'], 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-3 text-right font-mono text-base
                                           {{ bccomp($detalle['saldoFinal'], '0', 2) === -1 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100' }}">
                                    ${{ number_format((float) $detalle['saldoFinal'], 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-2 pb-2 text-xs font-normal text-gray-500">
                                    Saldo final {{ $detalle['esDeudora'] ? 'deudor' : 'acreedor' }}
                                    @if (bccomp($detalle['saldoFinal'], '0', 2) === -1)
                                        <span class="text-red-600">
                                            — el signo negativo indica naturaleza contraria a la esperada
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
