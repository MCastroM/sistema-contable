<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Empresas
            </h2>
            <a href="{{ route('empresas.create') }}"
               class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                + Nueva empresa
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

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">RUT</th>
                            <th class="px-4 py-3">Razón social</th>
                            <th class="px-4 py-3 text-center">Cuentas</th>
                            <th class="px-4 py-3 text-center">Períodos</th>
                            <th class="px-4 py-3 text-center">Comprobantes</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($empresas as $e)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 {{ ! $e->activa ? 'opacity-50' : '' }}">
                                <td class="px-4 py-3 font-mono">{{ $e->rut }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $e->razon_social }}</td>
                                <td class="px-4 py-3 text-center">{{ $e->cuentas_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $e->periodos_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $e->comprobantes_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $e->activa ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $e->activa ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('empresas.show', $e) }}"
                                       class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs">
                                        Ver / administrar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
