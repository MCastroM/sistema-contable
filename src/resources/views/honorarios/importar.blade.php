<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Importar Libro de Honorarios
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('honorarios.importar.previsualizar', $empresa) }}"
                  enctype="multipart/form-data"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Año</label>
                        <input type="number" name="anio" required value="{{ old('anio', now()->year) }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Mes</label>
                        <select name="mes" required class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                            @foreach (['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                                <option value="{{ $i+1 }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Archivo (CSV o Excel)</label>
                    <input type="file" name="archivo" required accept=".csv,.xlsx,.xls" class="w-full text-sm">
                    <p class="text-xs text-gray-400 mt-1">
                        Columnas esperadas: nro, boleta, fecha, rut, nombre, brutos, retencion, total
                    </p>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium">
                    Continuar
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
