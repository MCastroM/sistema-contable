{{--
    Nodo recursivo del árbol de cuentas.
    Usa <details>/<summary> nativos del navegador: expandible sin JavaScript.
    Nivel 1 (clases) parte abierto; el resto, cerrado.
--}}
@php
    $hijas = $porPadre->get($cuenta->id) ?? collect();
    $colorClase = [
        'activo'     => 'bg-blue-100 text-blue-800',
        'pasivo'     => 'bg-red-100 text-red-800',
        'patrimonio' => 'bg-purple-100 text-purple-800',
        'resultado'  => 'bg-emerald-100 text-emerald-800',
    ][$cuenta->clase] ?? 'bg-gray-100 text-gray-800';
@endphp

@if ($hijas->isEmpty())
    {{-- Hoja (imputable): fila simple --}}
    <div class="flex items-center gap-3 py-1.5 pl-6 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50"
         style="margin-left: {{ ($nivel - 1) * 1.25 }}rem">
        <span class="w-28 shrink-0 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $cuenta->codigo }}</span>
        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $cuenta->nombre }}</span>
        @if ($cuenta->imputable)
            <span class="text-xs text-gray-400" title="Imputable: recibe movimientos">●</span>
        @endif
        @unless ($cuenta->activa)
            <span class="text-xs px-1.5 rounded bg-gray-200 text-gray-500">inactiva</span>
        @endunless
    </div>
@else
    {{-- Agrupadora: expandible --}}
    <details @if($nivel === 1) open @endif class="group">
        <summary class="flex items-center gap-3 py-1.5 rounded cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-gray-700/50"
                 style="margin-left: {{ ($nivel - 1) * 1.25 }}rem">
            <span class="text-gray-400 text-xs w-3 transition-transform group-open:rotate-90">▶</span>
            <span class="w-28 shrink-0 font-mono text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $cuenta->codigo }}</span>
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $cuenta->nombre }}</span>
            @if ($nivel === 1)
                <span class="text-xs px-2 py-0.5 rounded-full {{ $colorClase }}">{{ $cuenta->clase }}</span>
            @endif
            <span class="text-xs text-gray-400">({{ $hijas->count() }})</span>
        </summary>

        @foreach ($hijas as $hija)
            @include('cuentas._nodo', ['cuenta' => $hija, 'porPadre' => $porPadre, 'nivel' => $nivel + 1])
        @endforeach
    </details>
@endif
