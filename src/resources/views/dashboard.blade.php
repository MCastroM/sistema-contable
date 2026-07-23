<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Panel de control
            </h2>

            {{-- Selector de empresa activa --}}
            @if ($empresas->count())
                <form method="POST" action="{{ route('empresa.seleccionar') }}" class="flex items-center gap-2">
                    @csrf
                    <label for="empresa_id" class="text-sm text-gray-600 dark:text-gray-400">Empresa:</label>
                    <select name="empresa_id" id="empresa_id"
                            onchange="this.form.submit()"
                            class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($empresas as $emp)
                            <option value="{{ $emp->id }}" @selected($empresa && $emp->id === $empresa->id)>
                                {{ $emp->razon_social }} ({{ $emp->rut }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-3 text-sm text-green-700 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ══════════ Indicadores económicos del día ══════════ --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                    Indicadores económicos
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach ([
                        'uf'    => ['UF', '$'],
                        'utm'   => ['UTM', '$'],
                        'dolar' => ['Dólar', '$'],
                        'ipc'   => ['IPC', '%'],
                        'tpm'   => ['TPM', '%'],
                    ] as $codigo => [$titulo, $simbolo])
                        @php $ind = $indicadores[$codigo]; @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $titulo }}</div>
                            @if ($ind)
                                <div class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">
                                    @if ($simbolo === '$')
                                        ${{ number_format((float) $ind->valor, str_contains($ind->valor, '.') && $codigo !== 'utm' ? 2 : 0, ',', '.') }}
                                    @else
                                        {{ number_format((float) $ind->valor, 2, ',', '.') }}%
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ $ind->fecha->format('d-m-Y') }}
                                </div>
                            @else
                                <div class="mt-1 text-sm text-gray-400 italic">sin datos</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ══════════ Resumen de la empresa activa ══════════ --}}
            @if ($empresa && $resumen)
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                        {{ $empresa->razon_social }} — RUT {{ $empresa->rut }}
                    </h3>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Período {{ now()->year }}</div>
                            @if ($resumen['periodo'])
                                <div class="mt-1 text-xl font-bold {{ $resumen['periodo']->estaCerrado() ? 'text-red-600' : 'text-green-600' }}">
                                    {{ ucfirst($resumen['periodo']->estado) }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    @if ($resumen['periodo']->fecha_bloqueo)
                                        Bloqueado hasta {{ $resumen['periodo']->fecha_bloqueo->format('d-m-Y') }}
                                    @else
                                        Sin fecha de bloqueo
                                    @endif
                                </div>
                            @else
                                <div class="mt-1 text-sm text-amber-600">No existe — créalo</div>
                            @endif
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Plan de cuentas</div>
                            <div class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $resumen['cuentas'] }}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">{{ $resumen['imputables'] }} imputables</div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Comprobantes aprobados</div>
                            <div class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $resumen['aprobados'] }}
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Borradores pendientes</div>
                            <div class="mt-1 text-xl font-bold {{ $resumen['borradores'] > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ $resumen['borradores'] }}
                            </div>
                        </div>

                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-gray-500">
                    No hay empresas registradas aún.
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
