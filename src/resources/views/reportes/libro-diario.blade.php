<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Libro diario
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $empresa->razon_social }}</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- ══════ Filtros (no se imprimen) ══════ --}}
            <form method="GET" action="{{ route('reportes.libro-diario') }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 print:hidden">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
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
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
                        <select name="tipo"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            <option value="">Todos</option>
                            <option value="I" @selected($tipoActivo === 'I')>Ingreso</option>
                            <option value="E" @selected($tipoActivo === 'E')>Egreso</option>
                            <option value="T" @selected($tipoActivo === 'T')>Traspaso</option>
                        </select>
                    </div>
                    <div class="space-y-1.5 pb-1">
                        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                            <input type="checkbox" name="incluir_borradores" value="1"
                                   @checked($incluirBorradores)
                                   class="rounded border-gray-300 text-amber-600">
                            Vista previa (borradores)
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                            <input type="checkbox" name="incluir_anulados" value="1"
                                   @checked($incluirAnulados)
                                   class="rounded border-gray-300 text-indigo-600">
                            Mostrar anulados
                        </label>
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

            {{-- ══════ Reporte ══════ --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">

                {{-- Encabezado formal --}}
                <div class="text-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">LIBRO DIARIO</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $empresa->razon_social }} — RUT {{ $empresa->rut }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Del {{ $desde->format('d-m-Y') }} al {{ $hasta->format('d-m-Y') }}
                        @if ($tipoActivo)
                            · Tipo: {{ ['I' => 'Ingreso', 'E' => 'Egreso', 'T' => 'Traspaso'][$tipoActivo] }}
                        @endif
                    </p>
                </div>

                {{-- ⚠ Marca de agua: este documento NO es el libro oficial --}}
                @if ($incluirBorradores && $conteoBorradores > 0)
                    <div class="mb-5 rounded-md border-2 border-amber-400 bg-amber-50 dark:bg-amber-900/20 px-4 py-2.5 text-center">
                        <p class="text-sm font-bold text-amber-800 dark:text-amber-300">
                            ⚠ VISTA PREVIA — INCLUYE {{ $conteoBorradores }} COMPROBANTE(S) EN BORRADOR
                        </p>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                            Documento de trabajo. No constituye el libro diario oficial.
                        </p>
                    </div>
                @endif

                @forelse ($comprobantes as $c)
                    @php
                        $esBorrador = $c->estado === 'borrador';
                        $esAnulado  = $c->estado === 'anulado';
                    @endphp

                    <div class="mb-6 {{ $esAnulado ? 'opacity-50' : '' }}
                                {{ $esBorrador ? 'border-l-4 border-amber-400 bg-amber-50/40 dark:bg-amber-900/10 pl-3 py-2 rounded-r' : '' }}">

                        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-1">
                            <div class="text-sm">
                                <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $c->folio() }}</span>
                                <span class="text-gray-500 dark:text-gray-400 ml-3">{{ $c->fecha->format('d-m-Y') }}</span>
                                <span class="text-gray-700 dark:text-gray-300 ml-3 {{ $esAnulado ? 'line-through' : '' }}">
                                    {{ $c->glosa }}
                                </span>
                                @if ($esBorrador)
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-200 text-amber-900 font-semibold">
                                        BORRADOR
                                    </span>
                                @elseif ($esAnulado)
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-600">ANULADO</span>
                                @endif
                            </div>
                            <a href="{{ route('comprobantes.show', $c) }}"
                               class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 print:hidden">
                                Ver comprobante
                            </a>
                        </div>

                        <table class="min-w-full text-sm">
                            <tbody>
                                @foreach ($c->movimientos as $m)
                                    <tr class="border-b border-gray-50 dark:border-gray-700/50">
                                        <td class="py-1.5 pl-4 w-28 font-mono text-gray-500 dark:text-gray-400">
                                            {{ $m->cuenta->codigo }}
                                        </td>
                                        <td class="py-1.5 text-gray-800 dark:text-gray-200">
                                            {{ $m->cuenta->nombre }}
                                            @if ($m->glosa)
                                                <span class="text-gray-400 text-xs ml-2">— {{ $m->glosa }}</span>
                                            @endif
                                            @if ($m->centroCosto)
                                                <span class="text-xs text-gray-400 ml-2">[CC: {{ $m->centroCosto->codigo }}]</span>
                                            @endif
                                        </td>
                                        <td class="py-1.5 text-right font-mono w-32 text-gray-900 dark:text-gray-100">
                                            {{ bccomp($m->debe, '0', 2) === 1 ? '$' . number_format((float) $m->debe, 0, ',', '.') : '' }}
                                        </td>
                                        <td class="py-1.5 text-right font-mono w-32 text-gray-900 dark:text-gray-100">
                                            {{ bccomp($m->haber, '0', 2) === 1 ? '$' . number_format((float) $m->haber, 0, ',', '.') : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="text-xs font-semibold">
                                    <td colspan="2" class="py-1 text-right text-gray-500 dark:text-gray-400">
                                        Suma asiento
                                        @if ($esBorrador && ! $c->estaCuadrado())
                                            <span class="text-red-600 ml-2">✖ descuadrado</span>
                                        @endif
                                    </td>
                                    <td class="py-1 text-right font-mono text-gray-600 dark:text-gray-300">
                                        ${{ number_format((float) $c->totalDebe(), 0, ',', '.') }}
                                    </td>
                                    <td class="py-1 text-right font-mono text-gray-600 dark:text-gray-300">
                                        ${{ number_format((float) $c->totalHaber(), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="text-center text-gray-400 py-10">
                        No hay asientos en el período seleccionado.
                    </p>
                @endforelse

                {{-- ══════ Totalización ══════ --}}
                @if ($comprobantes->isNotEmpty())
                    <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-4 mt-6 space-y-3">

                        {{-- Oficial: solo aprobados --}}
                        <table class="min-w-full text-sm">
                            <tr class="font-bold">
                                <td class="py-1 text-gray-900 dark:text-gray-100">
                                    TOTALES OFICIALES
                                    <span class="font-normal text-gray-500 text-xs ml-2">
                                        ({{ $conteoAprobados }} asientos aprobados)
                                    </span>
                                </td>
                                <td class="py-1 text-right font-mono w-32 text-gray-900 dark:text-gray-100">
                                    ${{ number_format((float) $oficialDebe, 0, ',', '.') }}
                                </td>
                                <td class="py-1 text-right font-mono w-32 text-gray-900 dark:text-gray-100">
                                    ${{ number_format((float) $oficialHaber, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="pb-2 text-xs">
                                    @if (bccomp($oficialDebe, $oficialHaber, 2) === 0)
                                        <span class="text-green-600">✔ Los asientos aprobados cuadran</span>
                                    @else
                                        <span class="text-red-600">
                                            ✖ Descuadre de ${{ number_format(abs((float) bcsub($oficialDebe, $oficialHaber, 2)), 0, ',', '.') }}
                                            — revisar (no debería ocurrir)
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        {{-- Proyectado: aprobados + borradores --}}
                        @if ($incluirBorradores && $conteoBorradores > 0)
                            <table class="min-w-full text-sm border-t border-dashed border-amber-300 pt-2">
                                <tr class="font-bold text-amber-800 dark:text-amber-300">
                                    <td class="py-1">
                                        TOTALES PROYECTADOS
                                        <span class="font-normal text-xs ml-2">
                                            (incluye {{ $conteoBorradores }} borrador(es) — simulación)
                                        </span>
                                    </td>
                                    <td class="py-1 text-right font-mono w-32">
                                        ${{ number_format((float) $proyectadoDebe, 0, ',', '.') }}
                                    </td>
                                    <td class="py-1 text-right font-mono w-32">
                                        ${{ number_format((float) $proyectadoHaber, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-xs">
                                        @if ($hayDescuadreBorrador)
                                            <span class="text-red-600">
                                                ✖ Hay borradores descuadrados: no podrán aprobarse hasta corregirlos
                                                (ve a Comprobantes → Borradores).
                                            </span>
                                        @elseif (bccomp($proyectadoDebe, $proyectadoHaber, 2) === 0)
                                            <span class="text-amber-700 dark:text-amber-400">
                                                ✔ Todos los borradores cuadran: al aprobarlos, el período quedaría cuadrado
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        @endif

                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
