<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Editar cuenta — {{ $cuenta->codigo }} {{ $cuenta->nombre }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('cuentas.update', $cuenta) }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                @csrf
                @method('PUT')
                @include('cuentas._form')

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium">
                        Guardar cambios
                    </button>
                    <a href="{{ route('cuentas.index') }}" class="px-5 py-2.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                        Cancelar
                    </a>
                </div>
            </form>

            <form method="POST" action="{{ route('cuentas.toggle-activa', $cuenta) }}" class="mt-4">
                @csrf
                <button type="submit"
                        class="px-4 py-2 rounded-md text-sm font-medium {{ $cuenta->activa ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                    {{ $cuenta->activa ? 'Desactivar cuenta' : 'Reactivar cuenta' }}
                </button>
                <p class="text-xs text-gray-400 mt-1">
                    Las cuentas nunca se eliminan (podrían tener historial) — desactivarla solo evita que
                    aparezca como opción en formularios nuevos.
                </p>
            </form>
        </div>
    </div>
</x-app-layout>
