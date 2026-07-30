<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Nueva cuenta — {{ $empresa->razon_social }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('cuentas.store') }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                @csrf
                @include('cuentas._form')

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium">
                        Crear cuenta
                    </button>
                    <a href="{{ route('cuentas.index') }}" class="px-5 py-2.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
