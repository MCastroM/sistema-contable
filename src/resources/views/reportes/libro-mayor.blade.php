<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Libro mayor
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $empresa->razon_social }}</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filtros --}}
            <form method="GET" action="{{ route('reportes.libro-mayor') }}"
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">LIBRO MAYOR — RESUMEN POR CUENTA</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $empresa->razon_social }} — RUT {{ $empresa->rut }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Del {{ $desde->format('d-m-Y') }} al {{ $hasta->format('d-m-Y') }}
                        · Solo comprobantes aprobados
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-2 py-2 text-left">Código</th>
                                <th class="px-2 py-2 text-left">Cuenta</th>
                                <th class="px-2 py-2 text-right">Saldo anterior</th>
                                <th class="px-2 py-2 text-right">Débitos</th>
                                <th class="px-2 py-2 text-right">Créditos</th>
                                <th class="px-2 py-2 text-right">Saldo final</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($filas as $f)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-2 py-2 font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $f->cuenta->codigo }}
                                    </td>
                                    <td class="px-2 py-2 text-gray-800 dark:text-gray-200">
                                        {{ $f->cuenta->nombre }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono text-gray-500 dark:text-gray-400">
                                        {{ bccomp($f->saldoAnterior, '0', 2) === 0 ? '—' : '$' . number_format((float) $f->saldoAnterior, 0, ',', '.') }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono">
                                        {{ bccomp($f->debe, '0', 2) === 1 ? '$' . number_format((float) $f->debe, 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono">
                                        {{ bccomp($f->haber, '0', 2) === 1 ? '$' . number_format((float) $f->haber, 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono font-semibold
                                               {{ bccomp($f->saldoFinal, '0', 2) === -1 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100' }}">
                                        ${{ number_format((float) $f->saldoFinal, 0, ',', '.') }}
                                        <span class="text-xs font-normal text-gray-400">
                                            {{ $f->esDeudora ? 'D' : 'A' }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-right print:hidden">
                                        <a href="{{ route('reportes.libro-mayor.cuenta', ['cuenta' => $f->cuenta, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}"
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs">
                                            Detalle
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-2 py-10 text-center text-gray-400">
                                        No hay movimientos aprobados en el período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($filas->isNotEmpty())
                            <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                                <tr>
                                    <td colspan="3" class="px-2 py-3 text-gray-900 dark:text-gray-100">
                                        TOTALES DEL PERÍODO
                                        <span class="font-normal text-xs text-gray-500 ml-2">
                                            ({{ $filas->count() }} cuentas con movimiento)
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-right font-mono">${{ number_format((float) $totalDebe, 0, ',', '.') }}</td>
                                    <td class="px-2 py-3 text-right font-mono">${{ number_format((float) $totalHaber, 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="px-2 pb-2 text-xs font-normal">
                                        @if (bccomp($totalDebe, $totalHaber, 2) === 0)
                                            <span class="text-green-600">✔ Débitos = Créditos: la contabilidad cuadra</span>
                                        @else
                                            <span class="text-red-600">✖ Descuadre — revisar</span>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <p class="mt-4 text-xs text-gray-400">
                    D = saldo deudor (activos y pérdidas) · A = saldo acreedor (pasivos, patrimonio y ganancias).
                    Un monto en rojo indica saldo de naturaleza contraria a la esperada.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
