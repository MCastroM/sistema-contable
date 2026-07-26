<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Revisar antes de importar
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="rounded-md bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-600 dark:text-gray-300">
                El archivo trae <strong>{{ $totalFilas }}</strong> líneas en
                <strong>{{ $totalComprobantes }}</strong> comprobantes.
            </div>

            @if ($fechasInvalidas->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-sm font-semibold text-red-600 mb-2">
                        ⚠ {{ $fechasInvalidas->count() }} comprobante(s) con fecha no interpretable
                    </h3>
                    <p class="text-xs text-gray-500 mb-3">
                        Estos NO se importarán. Corrige la fecha en el archivo origen y vuelve a
                        importar solo esos comprobantes después, o continúa ahora e ignóralos.
                    </p>
                    <table class="text-xs w-full">
                        <tr class="text-left text-gray-400"><th>Comprobante</th><th>Fecha tal como venía</th></tr>
                        @foreach ($fechasInvalidas as $f)
                            <tr><td class="font-mono">{{ $f['comprobante'] }}</td><td class="font-mono">{{ $f['fecha_raw'] }}</td></tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if ($pendientes->isNotEmpty())
                <form method="POST" action="{{ route('diario.importar.confirmar', $empresa) }}"
                      class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="anio" value="{{ $anio }}">
                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                    <input type="hidden" name="ruta_archivo" value="{{ $rutaArchivo }}">

                    <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-400">
                        {{ $pendientes->count() }} código(s) de cuenta sin mapeo
                    </h3>

                    @foreach ($pendientes as $i => $cod)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end border-b border-gray-100 dark:border-gray-700 pb-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Código origen</label>
                                <div class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $cod }}</div>
                                <input type="hidden" name="mapeo[{{ $i }}][cod]" value="{{ $cod }}">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Cuenta real</label>
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
                        Confirmar mapeo e importar
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('diario.importar.confirmar', $empresa) }}">
                    @csrf
                    <input type="hidden" name="anio" value="{{ $anio }}">
                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                    <input type="hidden" name="ruta_archivo" value="{{ $rutaArchivo }}">
                    <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium">
                        Todas las cuentas ya mapeadas — Importar ahora
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
