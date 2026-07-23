<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Comprobante <span class="font-mono">{{ $comprobante->folio() }}</span>
            </h2>
            <a href="{{ route('comprobantes.index') }}"
               class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                ← Volver al listado
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-3 text-sm text-green-700 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif
            @error('accion')
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror
            @error('motivo')
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror

            {{-- Encabezado del comprobante --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Estado</div>
                        @php
                            $badge = ['borrador' => 'bg-amber-100 text-amber-800',
                                      'aprobado' => 'bg-green-100 text-green-800',
                                      'anulado'  => 'bg-gray-200 text-gray-600'][$comprobante->estado];
                        @endphp
                        <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-xs {{ $badge }}">
                            {{ $comprobante->estado }}
                        </span>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Fecha</div>
                        <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $comprobante->fecha->format('d-m-Y') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Creado por</div>
                        <div class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ $comprobante->creadoPor->name }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Aprobado por</div>
                        <div class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ $comprobante->aprobadoPor?->name ?? '—' }}
                            @if ($comprobante->aprobado_at)
                                <span class="text-xs text-gray-400">({{ $comprobante->aprobado_at->format('d-m-Y H:i') }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-span-2 md:col-span-4">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Glosa</div>
                        <div class="mt-1 text-gray-900 dark:text-gray-100">{{ $comprobante->glosa }}</div>
                    </div>
                </div>
            </div>

            {{-- Líneas del asiento --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3">Glosa línea</th>
                            <th class="px-4 py-3 text-right">Debe</th>
                            <th class="px-4 py-3 text-right">Haber</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($comprobante->movimientos as $m)
                            <tr>
                                <td class="px-4 py-2.5">
                                    <span class="font-mono text-gray-500 dark:text-gray-400">{{ $m->cuenta->codigo }}</span>
                                    <span class="text-gray-900 dark:text-gray-100 ml-2">{{ $m->cuenta->nombre }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $m->glosa ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-mono">
                                    {{ bccomp($m->debe, '0', 2) === 1 ? '$' . number_format((float) $m->debe, 0, ',', '.') : '' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono">
                                    {{ bccomp($m->haber, '0', 2) === 1 ? '$' . number_format((float) $m->haber, 0, ',', '.') : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700/50 font-semibold">
                        <tr>
                            <td class="px-4 py-3" colspan="2">Totales</td>
                            <td class="px-4 py-3 text-right font-mono">
                                ${{ number_format((float) $comprobante->totalDebe(), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                ${{ number_format((float) $comprobante->totalHaber(), 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-xs" colspan="4">
                                @if ($comprobante->estaCuadrado())
                                    <span class="text-green-600">✔ Cuadrado</span>
                                @else
                                    <span class="text-red-600">✖ Descuadrado — no se podrá aprobar</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Acciones según estado --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Acciones</h3>

                @if ($comprobante->estado === 'borrador')
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('comprobantes.aprobar', $comprobante) }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">
                                ✔ Aprobar
                            </button>
                        </form>

                        <form method="POST" action="{{ route('comprobantes.eliminar', $comprobante) }}"
                              onsubmit="return confirm('¿Eliminar definitivamente este borrador?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                                🗑 Eliminar borrador
                            </button>
                        </form>
                    </div>

                @elseif ($comprobante->estado === 'aprobado')
                    <form method="POST" action="{{ route('comprobantes.anular', $comprobante) }}"
                          class="flex flex-wrap items-end gap-3"
                          onsubmit="return confirm('¿Anular este comprobante? La acción queda en bitácora.');">
                        @csrf
                        <div class="flex-1 min-w-64">
                            <label for="motivo" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                Motivo de anulación (obligatorio)
                            </label>
                            <input type="text" name="motivo" id="motivo" required minlength="5"
                                   value="{{ old('motivo') }}"
                                   placeholder="Ej: factura emitida por error"
                                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        </div>
                        <button type="submit"
                                class="px-4 py-2 rounded-md bg-gray-700 text-white text-sm font-medium hover:bg-gray-800">
                            Anular comprobante
                        </button>
                    </form>

                @else
                    <p class="text-sm text-gray-500">
                        Comprobante anulado: sin acciones disponibles. El detalle queda como registro histórico.
                    </p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
