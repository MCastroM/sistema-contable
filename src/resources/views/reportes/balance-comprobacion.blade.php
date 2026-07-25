<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Balance de comprobación
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $empresa->razon_social }}</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filtros --}}
            <form method="GET" action="{{ route('reportes.balance-comprobacion') }}"
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">BALANCE DE COMPROBACIÓN</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $empresa->razon_social }} — RUT {{ $empresa->rut }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Del {{ $desde->format('d-m-Y') }} al {{ $hasta->format('d-m-Y') }}
                        · Solo comprobantes aprobados · {{ $cantidad }} cuentas con movimiento
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-2 py-2 text-left" rowspan="2">Código</th>
                                <th class="px-2 py-2 text-left" rowspan="2">Cuenta</th>
                                <th class="px-2 py-2 text-center" colspan="2">Sumas</th>
                                <th class="px-2 py-2 text-center" colspan="2">Saldos</th>
                            </tr>
                            <tr>
                                <th class="px-2 py-1 text-right">Debe</th>
                                <th class="px-2 py-1 text-right">Haber</th>
                                <th class="px-2 py-1 text-right">Deudor</th>
                                <th class="px-2 py-1 text-right">Acreedor</th>
                            </tr>
                        </thead>

                        @php
                            $etiquetasClase = ['activo' => 'ACTIVO', 'pasivo' => 'PASIVO',
                                               'patrimonio' => 'PATRIMONIO', 'resultado' => 'RESULTADO (Ganancias y Pérdidas)'];
                            $colorClase = ['activo' => 'text-blue-700 dark:text-blue-400',
                                           'pasivo' => 'text-red-700 dark:text-red-400',
                                           'patrimonio' => 'text-purple-700 dark:text-purple-400',
                                           'resultado' => 'text-emerald-700 dark:text-emerald-400'];
                        @endphp

                        @forelse ($etiquetasClase as $clase => $etiqueta)
                            @continue(! $porClase->has($clase))
                            <tbody>
                                <tr>
                                    <td colspan="6" class="pt-4 pb-1 px-2 text-xs font-bold {{ $colorClase[$clase] }}">
                                        {{ $etiqueta }}
                                    </td>
                                </tr>
                                @php
                                    $subDebe = $subHaber = $subDeudor = $subAcreedor = '0';
                                @endphp
                                @foreach ($porClase[$clase] as $f)
                                    @php
                                        $subDebe = bcadd($subDebe, $f->debe, 2);
                                        $subHaber = bcadd($subHaber, $f->haber, 2);
                                        $subDeudor = bcadd($subDeudor, $f->saldoDeudor, 2);
                                        $subAcreedor = bcadd($subAcreedor, $f->saldoAcreedor, 2);
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 divide-y divide-gray-50">
                                        <td class="px-2 py-1.5 font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $f->cuenta->codigo }}
                                        </td>
                                        <td class="px-2 py-1.5 text-gray-800 dark:text-gray-200">
                                            {{ $f->cuenta->nombre }}
                                        </td>
                                        <td class="px-2 py-1.5 text-right font-mono">
                                            {{ bccomp($f->debe, '0', 2) === 1 ? number_format((float) $f->debe, 0, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-2 py-1.5 text-right font-mono">
                                            {{ bccomp($f->haber, '0', 2) === 1 ? number_format((float) $f->haber, 0, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-2 py-1.5 text-right font-mono">
                                            {{ bccomp($f->saldoDeudor, '0', 2) === 1 ? number_format((float) $f->saldoDeudor, 0, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-2 py-1.5 text-right font-mono">
                                            {{ bccomp($f->saldoAcreedor, '0', 2) === 1 ? number_format((float) $f->saldoAcreedor, 0, ',', '.') : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="text-xs font-semibold border-t border-gray-200 dark:border-gray-700">
                                    <td colspan="2" class="px-2 py-1.5 text-right text-gray-500 dark:text-gray-400">
                                        Subtotal {{ $etiqueta }}
                                    </td>
                                    <td class="px-2 py-1.5 text-right font-mono">${{ number_format((float) $subDebe, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">${{ number_format((float) $subHaber, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">${{ number_format((float) $subDeudor, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">${{ number_format((float) $subAcreedor, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        @empty
                        @endforelse

                        @if ($cantidad === 0)
                            <tbody>
                                <tr><td colspan="6" class="px-2 py-10 text-center text-gray-400">
                                    No hay movimientos aprobados en el período.
                                </td></tr>
                            </tbody>
                        @endif

                        @if ($cantidad > 0)
                            <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                                <tr>
                                    <td colspan="2" class="px-2 py-3 text-gray-900 dark:text-gray-100">TOTALES GENERALES</td>
                                    <td class="px-2 py-3 text-right font-mono">${{ number_format((float) $totales['debe'], 0, ',', '.') }}</td>
                                    <td class="px-2 py-3 text-right font-mono">${{ number_format((float) $totales['haber'], 0, ',', '.') }}</td>
                                    <td class="px-2 py-3 text-right font-mono">${{ number_format((float) $totales['saldoDeudor'], 0, ',', '.') }}</td>
                                    <td class="px-2 py-3 text-right font-mono">${{ number_format((float) $totales['saldoAcreedor'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="px-2 pb-2 text-xs font-normal">
                                        @php
                                            $cuadraSumas  = bccomp($totales['debe'], $totales['haber'], 2) === 0;
                                            $cuadraSaldos = bccomp($totales['saldoDeudor'], $totales['saldoAcreedor'], 2) === 0;
                                        @endphp
                                        @if ($cuadraSumas && $cuadraSaldos)
                                            <span class="text-green-600">
                                                ✔ Sumas iguales y saldos iguales: la contabilidad está en equilibrio
                                            </span>
                                        @else
                                            <span class="text-red-600">
                                                ✖ Descuadre —
                                                @unless($cuadraSumas) sumas debe/haber no coinciden. @endunless
                                                @unless($cuadraSaldos) saldos deudor/acreedor no coinciden. @endunless
                                                Revisar.
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
