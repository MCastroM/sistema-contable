<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Asignar cuentas — {{ $pendientes->count() }} proveedor(es) nuevo(s)
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4 rounded-md bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-300">
                Este archivo trae {{ $totalFilas }} documento(s). Los proveedores de abajo aún no tienen
                una cuenta de gasto asociada — asígnala una vez y quedará memorizada para futuras importaciones.
            </div>

            <form method="POST" action="{{ route('compras.importar.confirmar', $empresa) }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                @csrf
                <input type="hidden" name="anio" value="{{ $anio }}">
                <input type="hidden" name="mes" value="{{ $mes }}">
                <input type="hidden" name="ruta_archivo" value="{{ $rutaArchivo }}">

                @foreach ($pendientes as $i => $prov)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Proveedor</label>
                            <div class="text-sm">
                                <span class="font-mono text-gray-500">{{ $prov['rut'] }}</span>
                                <span class="text-gray-900 dark:text-gray-100 ml-2">{{ $prov['razon_social'] }}</span>
                            </div>
                            <input type="hidden" name="mapeo[{{ $i }}][rut]" value="{{ $prov['rut'] }}">
                            <input type="hidden" name="mapeo[{{ $i }}][razon_social]" value="{{ $prov['razon_social'] }}">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Cuenta de gasto</label>
                            <select name="mapeo[{{ $i }}][cuenta_id]" required
                                    class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                                <option value="">— Selecciona —</option>
                                @foreach ($cuentas as $c)
                                    <option value="{{ $c->id }}">{{ $c->codigo }} · {{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium">
                    Confirmar e importar
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
