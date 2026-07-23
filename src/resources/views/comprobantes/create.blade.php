<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Nuevo comprobante
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $empresa->razon_social }}</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @error('accion')
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror
            @if ($errors->any() && ! $errors->has('accion'))
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{--
                x-data define el ESTADO Alpine del formulario:
                líneas, totales calculados y helpers. Todo lo que empieza
                con x- o : es Alpine reaccionando a ese estado.
            --}}
            <form method="POST" action="{{ route('comprobantes.store') }}"
                  x-data="formularioComprobante()">
                @csrf

                {{-- ══════ Encabezado ══════ --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
                            <select name="tipo" required
                                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <option value="I" @selected(old('tipo') === 'I')>Ingreso</option>
                                <option value="E" @selected(old('tipo') === 'E')>Egreso</option>
                                <option value="T" @selected(old('tipo') === 'T')>Traspaso</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Fecha</label>
                            <input type="date" name="fecha" required
                                   value="{{ old('fecha', now()->toDateString()) }}"
                                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Glosa general</label>
                            <input type="text" name="glosa" required minlength="3" maxlength="300"
                                   value="{{ old('glosa') }}"
                                   placeholder="Ej: Venta de servicios julio"
                                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        </div>
                    </div>
                </div>

                {{-- ══════ Líneas del asiento ══════ --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto mb-4">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 w-2/5">Cuenta</th>
                                <th class="px-4 py-3">Glosa línea (opcional)</th>
                                <th class="px-4 py-3 text-right w-36">Debe</th>
                                <th class="px-4 py-3 text-right w-36">Haber</th>
                                <th class="px-4 py-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            {{-- template x-for: Alpine pinta una fila por cada línea del estado --}}
                            <template x-for="(linea, i) in lineas" :key="i">
                                <tr>
                                    <td class="px-4 py-2">
                                        <select :name="`lineas[${i}][cuenta_id]`" x-model="linea.cuenta_id" required
                                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                            <option value="">— Selecciona cuenta —</option>
                                            @foreach ($cuentas as $cuenta)
                                                <option value="{{ $cuenta->id }}">{{ $cuenta->codigo }} · {{ $cuenta->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" :name="`lineas[${i}][glosa]`" x-model="linea.glosa"
                                               maxlength="300"
                                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" :name="`lineas[${i}][debe]`" x-model.number="linea.debe"
                                               @input="if (linea.debe > 0) linea.haber = 0"
                                               min="0" step="1" placeholder="0"
                                               class="w-full text-sm text-right rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" :name="`lineas[${i}][haber]`" x-model.number="linea.haber"
                                               @input="if (linea.haber > 0) linea.debe = 0"
                                               min="0" step="1" placeholder="0"
                                               class="w-full text-sm text-right rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <button type="button" @click="quitarLinea(i)"
                                                x-show="lineas.length > 2"
                                                title="Quitar línea"
                                                class="text-gray-400 hover:text-red-600">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                            <tr class="font-semibold">
                                <td class="px-4 py-3" colspan="2">
                                    <button type="button" @click="agregarLinea()"
                                            class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        + Agregar línea
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-right font-mono" x-text="clp(totalDebe)"></td>
                                <td class="px-4 py-3 text-right font-mono" x-text="clp(totalHaber)"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="px-4 py-2 text-sm">
                                    {{-- La cuadratura EN VIVO --}}
                                    <span x-show="cuadrado" class="text-green-600 font-medium">
                                        ✔ Cuadrado — listo para guardar
                                    </span>
                                    <span x-show="!cuadrado" class="text-red-600 font-medium">
                                        ✖ Diferencia: <span class="font-mono" x-text="clp(Math.abs(totalDebe - totalHaber))"></span>
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ══════ Guardar ══════ --}}
                <div class="flex items-center gap-4">
                    <button type="submit"
                            :disabled="!puedeGuardar"
                            :class="puedeGuardar
                                ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                : 'bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed'"
                            class="px-5 py-2.5 rounded-md text-sm font-medium">
                        Guardar borrador
                    </button>
                    <a href="{{ route('comprobantes.index') }}"
                       class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancelar</a>
                    <p class="text-xs text-gray-400">
                        Se guarda como borrador; la aprobación es un paso posterior.
                    </p>
                </div>
            </form>

        </div>
    </div>

    <script>
        function formularioComprobante() {
            return {
// Estado inicial: lo escrito antes de un error de validación
                // (old input), o 2 líneas vacías si es la primera carga.
                @php
                    $lineasIniciales = old('lineas', [
                        ['cuenta_id' => '', 'glosa' => '', 'debe' => null, 'haber' => null],
                        ['cuenta_id' => '', 'glosa' => '', 'debe' => null, 'haber' => null],
                    ]);
                @endphp
                lineas: {{ Illuminate\Support\Js::from($lineasIniciales) }},

                agregarLinea() {
                    this.lineas.push({ cuenta_id: '', glosa: '', debe: null, haber: null });
                },
                quitarLinea(i) {
                    this.lineas.splice(i, 1);
                },

                // Getters: Alpine los recalcula automáticamente con cada tecla
                get totalDebe()  { return this.lineas.reduce((s, l) => s + (Number(l.debe)  || 0), 0); },
                get totalHaber() { return this.lineas.reduce((s, l) => s + (Number(l.haber) || 0), 0); },
                get cuadrado()   { return this.totalDebe > 0 && this.totalDebe === this.totalHaber; },
                get lineasValidas() {
                    return this.lineas.length >= 2 && this.lineas.every(l =>
                        l.cuenta_id !== '' &&
                        ((Number(l.debe) || 0) > 0) !== ((Number(l.haber) || 0) > 0)
                    );
                },
                get puedeGuardar() { return this.cuadrado && this.lineasValidas; },

                clp(n) {
                    return '$' + new Intl.NumberFormat('es-CL').format(n || 0);
                },
            };
        }
    </script>
</x-app-layout>
