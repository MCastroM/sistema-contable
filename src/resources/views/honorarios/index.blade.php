<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Libro de Honorarios
            </h2>
            <a href="{{ route('honorarios.importar', $empresa) }}"
               class="px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                Importar archivo
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-3 text-sm text-green-700 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif
            @error('accion')
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</div>
            @enderror

            <form method="GET" action="{{ route('honorarios.index', $empresa) }}"
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
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2">N°</th><th class="px-3 py-2">Boleta</th><th class="px-3 py-2">RUT</th>
                                <th class="px-3 py-2">Nombre</th><th class="px-3 py-2">Fecha</th>
                                <th class="px-3 py-2 text-right">Brutos</th><th class="px-3 py-2 text-right">Retención</th>
                                <th class="px-3 py-2 text-right">Total</th><th class="px-3 py-2">Cuenta</th><th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($boletas as $b)
                                <tr class="{{ $b->cuenta_gasto_id ? '' : 'bg-amber-50 dark:bg-amber-900/10' }}">
                                    <td class="px-3 py-2">{{ $b->nro }}</td>
                                    <td class="px-3 py-2">{{ $b->boleta }}</td>
                                    <td class="px-3 py-2 font-mono">{{ $b->rut_prestador }}</td>
                                    <td class="px-3 py-2">{{ $b->nombre_prestador }}</td>
                                    <td class="px-3 py-2">{{ $b->fecha->format('d-m-Y') }}</td>
                                    <td class="px-3 py-2 text-right font-mono">${{ number_format((float) $b->brutos, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-mono">${{ number_format((float) $b->retencion, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-mono">${{ number_format((float) $b->total, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2">{{ $b->cuentaGasto?->nombre ?? '⚠ sin asignar' }}</td>
                                    <td class="px-3 py-2">{{ $b->estaCentralizado() ? '✔ centralizado' : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-3 py-10 text-center text-gray-400">Sin boletas este mes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($boletas->isNotEmpty() && $boletas->whereNull('comprobante_id')->isNotEmpty())
                    <form method="POST" action="{{ route('honorarios.centralizar', $empresa) }}">
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
