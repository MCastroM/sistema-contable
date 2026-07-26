<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Libro de Remuneraciones
            </h2>
            <a href="{{ route('remuneraciones.importar', $empresa) }}"
               class="px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                Importar archivo
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-3 text-sm text-green-700 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif
            @error('accion')
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</div>
            @enderror

            <form method="GET" action="{{ route('remuneraciones.index', $empresa) }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-end gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Año</label>
                    <input type="number" name="anio" value="{{ $anio }}" class="w-24 text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Mes</label>
                    <select name="mes" class="text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                        @foreach (['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                            <option value="{{ $i+1 }}" @selected($mes == $i+1)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm">Ver</button>
            </form>

            @if (! $periodo)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-amber-600">
                    No existe el período {{ $anio }} para esta empresa. Ábrelo primero en Empresas → {{ $empresa->razon_social }}.
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-2 py-2">RUT</th><th class="px-2 py-2">Nombre</th>
                                <th class="px-2 py-2 text-right">Sueldo</th><th class="px-2 py-2 text-right">Gratif.</th>
                                <th class="px-2 py-2 text-right">Total Hab.</th>
                                <th class="px-2 py-2 text-right">AFP</th><th class="px-2 py-2 text-right">Salud</th>
                                <th class="px-2 py-2 text-right">Imp.Único</th><th class="px-2 py-2 text-right">Préstamo</th>
                                <th class="px-2 py-2 text-right">Ahorro</th><th class="px-2 py-2 text-right">Anticipo</th>
                                <th class="px-2 py-2 text-right">Líquido</th><th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($filas as $f)
                                <tr>
                                    <td class="px-2 py-1.5 font-mono">{{ $f->rut_trabajador }}</td>
                                    <td class="px-2 py-1.5">{{ $f->nombre_trabajador }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->sueldo, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->gratificacion, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono font-semibold">{{ number_format((float) $f->total_haberes, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->afp, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->salud, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->impuesto_unico, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->prestamo, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->cuenta_ahorro, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format((float) $f->anticipo, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono font-semibold">{{ number_format((float) $f->liquido, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1.5">{{ $f->estaCentralizado() ? '✔' : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="13" class="px-2 py-10 text-center text-gray-400">Sin registros este mes.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($filas->isNotEmpty())
                            <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                                <tr>
                                    <td colspan="4" class="px-2 py-2 text-right">TOTALES</td>
                                    <td class="px-2 py-2 text-right font-mono">{{ number_format((float) $totales['total_haberes'], 0, ',', '.') }}</td>
                                    <td colspan="6"></td>
                                    <td class="px-2 py-2 text-right font-mono">{{ number_format((float) $totales['liquido'], 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if ($filas->isNotEmpty() && $filas->whereNull('comprobante_id')->isNotEmpty())
                    <form method="POST" action="{{ route('remuneraciones.centralizar', $empresa) }}">
                        @csrf
                        <input type="hidden" name="anio" value="{{ $anio }}">
                        <input type="hidden" name="mes" value="{{ $mes }}">
                        <button type="submit"
                                class="px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                            Centralizar mes al Diario
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
