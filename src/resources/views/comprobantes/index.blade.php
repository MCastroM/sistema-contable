<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Comprobantes
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $empresa->razon_social }}</div>
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
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror

            {{-- Filtros por estado --}}
            <div class="flex flex-wrap gap-2 text-sm">
                @foreach ([null => "Todos ({$conteos['todos']})",
                           'borrador' => "Borradores ({$conteos['borrador']})",
                           'aprobado' => "Aprobados ({$conteos['aprobado']})",
                           'anulado'  => "Anulados ({$conteos['anulado']})"] as $estado => $label)
                    <a href="{{ route('comprobantes.index', $estado ? ['estado' => $estado] : []) }}"
                       class="px-3 py-1.5 rounded-full border
                              {{ $estadoActivo === $estado
                                  ? 'bg-indigo-600 text-white border-indigo-600'
                                  : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-indigo-400' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Folio</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Glosa</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($comprobantes as $c)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 {{ $c->estado === 'anulado' ? 'opacity-50' : '' }}">
                                <td class="px-4 py-3 font-mono">{{ $c->folio() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $c->fecha->format('d-m-Y') }}</td>
                                <td class="px-4 py-3 max-w-xs truncate {{ $c->estado === 'anulado' ? 'line-through' : '' }}">
                                    {{ $c->glosa }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono">
                                    ${{ number_format((float) $c->total_debe, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = ['borrador' => 'bg-amber-100 text-amber-800',
                                                  'aprobado' => 'bg-green-100 text-green-800',
                                                  'anulado'  => 'bg-gray-200 text-gray-600'][$c->estado];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $badge }}">{{ $c->estado }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('comprobantes.show', $c) }}"
                                       class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                    No hay comprobantes {{ $estadoActivo ? "en estado {$estadoActivo}" : 'registrados' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $comprobantes->links() }}

        </div>
    </div>
</x-app-layout>
