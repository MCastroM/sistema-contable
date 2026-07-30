@php
    // Variables esperadas: $empresa, $cuentasPadre, opcional $cuenta (edicion)
    $esEdicion = isset($cuenta);
    $clases = ['activo' => 'Activo', 'pasivo' => 'Pasivo', 'patrimonio' => 'Patrimonio', 'resultado' => 'Resultado'];
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

@if ($esEdicion && ($tieneMovimientos ?? false))
    <div class="mb-4 rounded-md bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-300">
        Esta cuenta ya tiene movimientos registrados. Cambiar su <strong>clase</strong> o <strong>código</strong>
        puede alterar cómo se interpretan los reportes históricos (Balance, Mayor). Edita con cuidado.
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Código</label>
        <input type="text" name="codigo" required
               value="{{ old('codigo', $cuenta->codigo ?? '') }}"
               placeholder="Ej: 1.1.02.010"
               class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200 font-mono">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Clase</label>
        <select name="clase" required class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
            @foreach ($clases as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('clase', $cuenta->clase ?? '') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <label class="block text-xs text-gray-500 mb-1">Nombre</label>
    <input type="text" name="nombre" required
           value="{{ old('nombre', $cuenta->nombre ?? '') }}"
           class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
</div>

<div class="mt-4">
    <label class="block text-xs text-gray-500 mb-1">Cuenta padre (opcional, para agrupar en el árbol)</label>
    <select name="padre_id" class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-200">
        <option value="">— Sin padre (cuenta raíz) —</option>
        @foreach ($cuentasPadre as $p)
            <option value="{{ $p->id }}" @selected((string) old('padre_id', $cuenta->padre_id ?? '') === (string) $p->id)>
                {{ $p->codigo }} · {{ $p->nombre }}
            </option>
        @endforeach
    </select>
</div>

<div class="mt-4 flex items-center gap-2">
    <input type="checkbox" name="imputable" id="imputable" value="1"
           @checked(old('imputable', $cuenta->imputable ?? true))
           class="rounded border-gray-300">
    <label for="imputable" class="text-sm text-gray-700 dark:text-gray-300">
        Imputable (puede recibir movimientos contables directamente)
    </label>
</div>
