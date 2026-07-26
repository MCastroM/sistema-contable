<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Importar Libro Diario histórico
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

            <div class="mb-4 rounded-md bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-300">
                Cada asiento se aprueba INMEDIATAMENTE al importarse (es historia
                consumada, no un borrador a revisar). Si un asiento viene
                descuadrado, quedará como BORRADOR y se reportará al final —
                nada se pierde, pero tampoco se fuerza a cuadrar.
            </div>

            <form method="POST" action="{{ route('diario.importar.previsualizar', $empresa) }}"
                  enctype="multipart/form-data"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Año del período</label>
                        <input type="number" name="anio" required value="{{ old('anio', now()->year) }}"
                               class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tipo a asignar</label>
                        <select name="tipo" required class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                            <option value="T">Traspaso (recomendado para históricos)</option>
                            <option value="I">Ingreso</option>
                            <option value="E">Egreso</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">El libro original no distingue tipo por asiento.</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Archivo (CSV o Excel)</label>
                    <input type="file" name="archivo" required accept=".csv,.xlsx,.xls" class="w-full text-sm">
                    <p class="text-xs text-gray-400 mt-1">
                        Columnas: cod, fecha, comprobante, glosa, cc, debe, haber<br>
                        "comprobante" es el N° original del libro (varias filas con el
                        mismo número = un solo asiento). Fechas: acepta DD.MM.AAAA,
                        DD,MM,AAAA o AAAA-MM-DD.
                    </p>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium">
                    Continuar
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
