<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Editar comprobante — {{ $comprobante->folio() }}
            <span class="text-sm font-normal text-gray-500">({{ $comprobante->estado }})</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @if ($comprobante->estado === 'aprobado')
                <div class="mb-4 rounded-md bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-300">
                    Este comprobante ya está <strong>aprobado</strong> — editarlo modifica historia contable
                    ya cerrada. Vas a tener que confirmar explícitamente antes de guardar.
                </div>
            @endif

            <form method="POST" action="{{ route('comprobantes.update', $comprobante) }}"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" id="form-edicion">
                @csrf
                @method('PUT')

                <div class="mb-4 text-sm text-gray-500">
                    <strong>{{ $comprobante->fecha->format('d-m-Y') }}</strong> — {{ $comprobante->glosa }}
                </div>

                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-2">Cuenta</th>
                            <th class="py-2">Glosa</th>
                            <th class="py-2 text-right">Debe</th>
                            <th class="py-2 text-right">Haber</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($comprobante->movimientos as $i => $m)
                            <tr>
                                <input type="hidden" name="lineas[{{ $i }}][id]" value="{{ $m->id }}">
                                <td class="py-2 pr-3">
                                    <select name="lineas[{{ $i }}][cuenta_id]" required
                                            class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                                        @foreach ($cuentas as $c)
                                            <option value="{{ $c->id }}" @selected($c->id === $m->cuenta_id)>
                                                {{ $c->codigo }} · {{ $c->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 pr-3">
                                    <input type="text" name="lineas[{{ $i }}][glosa]" value="{{ $m->glosa }}"
                                           class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                                </td>
                                <td class="py-2 pr-3 text-right">
                                    <input type="number" step="0.01" min="0" name="lineas[{{ $i }}][debe]"
                                           value="{{ $m->debe > 0 ? $m->debe : '' }}"
                                           class="linea-debe w-32 text-sm text-right rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                                </td>
                                <td class="py-2 text-right">
                                    <input type="number" step="0.01" min="0" name="lineas[{{ $i }}][haber]"
                                           value="{{ $m->haber > 0 ? $m->haber : '' }}"
                                           class="linea-haber w-32 text-sm text-right rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold border-t border-gray-200 dark:border-gray-700">
                            <td colspan="2" class="py-2 text-right pr-3">Totales:</td>
                            <td class="py-2 text-right pr-3"><span id="total-debe">0</span></td>
                            <td class="py-2 text-right"><span id="total-haber">0</span></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="pt-1 text-right text-xs">
                                <span id="estado-cuadratura" class="font-medium"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                @if ($comprobante->estado === 'aprobado')
                    <div class="mt-4 flex items-center gap-2">
                        <input type="checkbox" name="confirmar_aprobado" id="confirmar_aprobado" value="1"
                               class="rounded border-gray-300">
                        <label for="confirmar_aprobado" class="text-sm text-gray-700 dark:text-gray-300">
                            Entiendo que este comprobante ya está aprobado y confirmo que quiero editarlo igual.
                        </label>
                    </div>
                @endif

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-medium">
                        Guardar cambios
                    </button>
                    <a href="{{ route('comprobantes.show', $comprobante) }}"
                       class="px-5 py-2.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- JS simple: solo ayuda visual, mostrando si cuadra en vivo.
         La validacion REAL de cuadratura la hace el servidor al guardar. --}}
    <script>
        function recalcularTotales() {
            let totalDebe = 0, totalHaber = 0;
            document.querySelectorAll('.linea-debe').forEach(el => totalDebe += parseFloat(el.value || 0));
            document.querySelectorAll('.linea-haber').forEach(el => totalHaber += parseFloat(el.value || 0));

            document.getElementById('total-debe').textContent = totalDebe.toLocaleString('es-CL', {minimumFractionDigits: 2});
            document.getElementById('total-haber').textContent = totalHaber.toLocaleString('es-CL', {minimumFractionDigits: 2});

            const estadoEl = document.getElementById('estado-cuadratura');
            if (Math.abs(totalDebe - totalHaber) < 0.01) {
                estadoEl.textContent = '✔ Cuadrado';
                estadoEl.className = 'font-medium text-green-600';
            } else {
                estadoEl.textContent = `✖ Descuadrado (diferencia: ${(totalDebe - totalHaber).toLocaleString('es-CL', {minimumFractionDigits: 2})})`;
                estadoEl.className = 'font-medium text-red-600';
            }
        }

        document.querySelectorAll('.linea-debe, .linea-haber').forEach(el => {
            el.addEventListener('input', recalcularTotales);
        });
        recalcularTotales();
    </script>
</x-app-layout>
