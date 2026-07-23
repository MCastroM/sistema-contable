<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Plan de cuentas
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $empresa->razon_social }} · {{ $totales['total'] }} cuentas
                ({{ $totales['imputables'] }} imputables)
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">

                {{-- Leyenda --}}
                <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex flex-wrap gap-3 text-xs">
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">activo</span>
                    <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-800">pasivo</span>
                    <span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">patrimonio</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">resultado</span>
                    <span class="px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200">● imputable</span>
                </div>

                {{-- Árbol --}}
                <div class="p-4">
                    @forelse ($raices as $cuenta)
                        @include('cuentas._nodo', ['cuenta' => $cuenta, 'porPadre' => $porPadre, 'nivel' => 1])
                    @empty
                        <p class="text-gray-500 p-4">
                            Esta empresa no tiene plan de cuentas instalado.
                        </p>
                    @endforelse
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
