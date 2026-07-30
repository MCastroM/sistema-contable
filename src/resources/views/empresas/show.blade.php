<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $empresa->razon_social }}
            </h2>
            <a href="{{ route('empresas.index') }}"
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

            {{-- ══════ Datos de la empresa ══════ --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm flex-1">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">RUT</div>
                            <div class="mt-1 font-mono text-gray-900 dark:text-gray-100">{{ $empresa->rut }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Giro</div>
                            <div class="mt-1 text-gray-900 dark:text-gray-100">{{ $empresa->giro ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Cuentas</div>
                            <div class="mt-1 text-gray-900 dark:text-gray-100">{{ $resumen['cuentas'] }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Comprobantes</div>
                            <div class="mt-1 text-gray-900 dark:text-gray-100">{{ $resumen['comprobantes'] }}</div>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs shrink-0 {{ $empresa->activa ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                        {{ $empresa->activa ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('empresas.edit', $empresa) }}"
                       class="px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300">
                        Editar datos
                    </a>

                    @if ($resumen['cuentas'] === 0)
                        <form method="POST" action="{{ route('empresas.instalar-plan', $empresa) }}">
                            @csrf
                            <button type="submit"
                                    class="px-3 py-1.5 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                                Instalar plan de cuentas
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('empresas.toggle-activa', $empresa) }}"
                          onsubmit="return confirm('¿{{ $empresa->activa ? 'Desactivar' : 'Activar' }} esta empresa?');">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded-md border text-sm
                                       {{ $empresa->activa ? 'border-red-300 text-red-600' : 'border-green-300 text-green-600' }}">
                            {{ $empresa->activa ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- ══════ Períodos ══════ --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Períodos contables
                    </h3>

                    {{-- Abrir año nuevo --}}
                    <form method="POST" action="{{ route('periodos.abrir', $empresa) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="number" name="anio" min="2000" max="2100"
                               value="{{ ($empresa->periodos->max('anio') ?? now()->year - 1) + 1 }}"
                               class="w-24 text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <button type="submit"
                                class="px-3 py-1.5 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                            + Abrir período
                        </button>
                    </form>
                </div>

                <div class="space-y-3">
                    @forelse ($empresa->periodos as $p)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $p->anio }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs
                                                 {{ $p->estaCerrado() ? 'bg-gray-200 text-gray-600' : 'bg-green-100 text-green-800' }}">
                                        {{ ucfirst($p->estado) }}
                                    </span>
                                    @if ($p->fecha_bloqueo)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Bloqueado hasta {{ $p->fecha_bloqueo->format('d-m-Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if (! $p->estaCerrado())
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3 border-t border-gray-100 dark:border-gray-700">

                                    {{-- Generar asiento de cierre del ejercicio (traspaso de resultado) --}}
                                    <form method="POST" action="{{ route('empresas.cierre-ejercicio', $empresa) }}"
                                          onsubmit="return confirm('¿Generar el asiento de CIERRE DEL EJERCICIO {{ $p->anio }}? Dejará en cero las cuentas de Resultado y traspasará la utilidad/pérdida a Resultados acumulados.');"
                                          class="flex items-end">
                                        @csrf
                                        <input type="hidden" name="anio" value="{{ $p->anio }}">
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-md border border-indigo-300 text-indigo-600 dark:border-indigo-700 dark:text-indigo-400 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 whitespace-nowrap">
                                            Cierre de ejercicio {{ $p->anio }}
                                        </button>
                                    </form>

                                    {{-- Mover fecha de bloqueo --}}
                                    <form method="POST" action="{{ route('periodos.bloquear', [$empresa, $p]) }}"
                                          class="flex items-end gap-2">
                                        @csrf
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                Mover bloqueo hasta
                                            </label>
                                            <input type="date" name="fecha_bloqueo" required
                                                   min="{{ $p->anio }}-01-01" max="{{ $p->anio }}-12-31"
                                                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                        </div>
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                            Bloquear
                                        </button>
                                    </form>

                                    {{-- Cerrar período --}}
                                    <form method="POST" action="{{ route('periodos.cerrar', [$empresa, $p]) }}"
                                          onsubmit="return confirm('¿Cerrar el período {{ $p->anio }}? No se admitirán más comprobantes.');"
                                          class="flex items-end">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-md bg-gray-700 text-white text-sm hover:bg-gray-800">
                                            Cerrar período {{ $p->anio }}
                                        </button>
                                    </form>
                                </div>
                            @else
                                {{-- Reapertura: motivo obligatorio, siempre visible pero exigente --}}
                                <form method="POST" action="{{ route('periodos.reabrir', [$empresa, $p]) }}"
                                      onsubmit="return confirm('¿Reabrir un período CERRADO? Esta acción queda registrada en la bitácora de auditoría.');"
                                      class="pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-end gap-3">
                                    @csrf
                                    <div class="flex-1 min-w-64">
                                        <label class="block text-xs text-amber-700 dark:text-amber-400 mb-1">
                                            Motivo de reapertura (obligatorio, mín. 10 caracteres)
                                        </label>
                                        <input type="text" name="motivo" required minlength="10"
                                               placeholder="Ej: Observación SII, ajuste de depreciación pendiente"
                                               class="w-full text-sm rounded-md border-amber-300 dark:border-amber-700 dark:bg-gray-900 dark:text-gray-200">
                                    </div>
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-md bg-amber-600 text-white text-sm hover:bg-amber-700 whitespace-nowrap">
                                        ⚠ Reabrir período
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-6 text-center">
                            Esta empresa no tiene períodos abiertos. Usa "+ Abrir período" arriba.
                        </p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
