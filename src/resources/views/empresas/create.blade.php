<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Nueva empresa
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('empresas.store') }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">RUT *</label>
                        <input type="text" name="rut" required value="{{ old('rut') }}"
                               placeholder="76123456-7"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Razón social *</label>
                        <input type="text" name="razon_social" required value="{{ old('razon_social') }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Giro</label>
                        <input type="text" name="giro" value="{{ old('giro') }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Dirección</label>
                        <input type="text" name="direccion" value="{{ old('direccion') }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Comuna</label>
                        <input type="text" name="comuna" value="{{ old('comuna') }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="crear_periodo_actual" value="1" checked
                               class="rounded border-gray-300 text-indigo-600">
                        Abrir período {{ now()->year }} de inmediato
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="instalar_plan" value="1" checked
                               class="rounded border-gray-300 text-indigo-600">
                        Instalar plan de cuentas estándar chileno (~121 cuentas)
                    </label>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                        Crear empresa
                    </button>
                    <a href="{{ route('empresas.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
