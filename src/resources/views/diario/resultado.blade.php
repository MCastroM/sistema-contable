<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Resultado de la importación
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $ok }}</div>
                    <div class="text-xs text-gray-500">Comprobantes importados y aprobados</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold {{ count($errores) ? 'text-red-600' : 'text-gray-400' }}">{{ count($errores) }}</div>
                    <div class="text-xs text-gray-500">Comprobantes con error</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold {{ $omitidos ? 'text-amber-600' : 'text-gray-400' }}">{{ $omitidos }}</div>
                    <div class="text-xs text-gray-500">Líneas omitidas (fecha inválida)</div>
                </div>
            </div>

            @if (count($errores))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-sm font-semibold text-red-600 mb-3">
                        Comprobantes que NO se pudieron aprobar
                    </h3>
                    <p class="text-xs text-gray-500 mb-3">
                        Ningún dato se perdió: revisa el motivo, corrige en el archivo origen
                        y vuelve a importar solo esos comprobantes. Los descuadres de centavos
                        (arrastre de decimales del Excel original) suelen ser la causa más común.
                    </p>
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-400 text-left">
                            <tr><th class="py-1">N° comprobante original</th><th class="py-1">Motivo</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($errores as $e)
                                <tr>
                                    <td class="py-1.5 font-mono">{{ $e['comprobante'] }}</td>
                                    <td class="py-1.5 text-red-600">{{ $e['error'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="flex gap-3">
                <a href="{{ route('comprobantes.index') }}"
                   class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium">
                    Ver comprobantes
                </a>
                <a href="{{ route('diario.importar', $empresa) }}"
                   class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                    Importar otro archivo
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
