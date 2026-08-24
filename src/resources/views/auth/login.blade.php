<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — {{ config('app.name', 'Sistema Contable') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts / estilos -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex">

        {{-- ── Panel izquierdo (marca) ───────────────────────────── --}}
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12"
             style="background-color: #0F6E56;">

            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center rounded-xl"
                     style="width:40px;height:40px;background-color:#E1F5EE;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                         fill="none" stroke="#0F6E56" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 19a9 9 0 0 1 9 0 9 9 0 0 1 9 0"/>
                        <path d="M3 6a9 9 0 0 1 9 0 9 9 0 0 1 9 0"/>
                        <line x1="3" y1="6" x2="3" y2="19"/>
                        <line x1="12" y1="6" x2="12" y2="19"/>
                        <line x1="21" y1="6" x2="21" y2="19"/>
                    </svg>
                </div>
                <span class="text-lg font-medium" style="color:#E1F5EE;">Sistema Contable</span>
            </div>

            <div>
                <h1 class="text-3xl font-semibold text-white leading-snug mb-4">
                    Contabilidad clara,<br>ordenada y al día
                </h1>
                <p class="text-base leading-relaxed" style="color:#9FE1CB;">
                    Libros, balances y reportes en un solo lugar.
                    Ingresa para continuar con tu trabajo.
                </p>
            </div>

            <div class="flex gap-8">
                <div>
                    <div class="text-xl font-medium text-white">Diario</div>
                    <div class="text-sm" style="color:#5DCAA5;">y Mayor</div>
                </div>
                <div>
                    <div class="text-xl font-medium text-white">Balances</div>
                    <div class="text-sm" style="color:#5DCAA5;">8 columnas</div>
                </div>
                <div>
                    <div class="text-xl font-medium text-white">Libros</div>
                    <div class="text-sm" style="color:#5DCAA5;">timbrados</div>
                </div>
            </div>
        </div>

        {{-- ── Panel derecho (formulario) ────────────────────────── --}}
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center bg-gray-50 px-6 py-12">
            <div class="w-full max-w-sm">

                {{-- Logo visible solo en móvil (cuando el panel izquierdo se oculta) --}}
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="flex items-center justify-center rounded-xl"
                         style="width:40px;height:40px;background-color:#0F6E56;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                             fill="none" stroke="#E1F5EE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 19a9 9 0 0 1 9 0 9 9 0 0 1 9 0"/>
                            <path d="M3 6a9 9 0 0 1 9 0 9 9 0 0 1 9 0"/>
                            <line x1="3" y1="6" x2="3" y2="19"/>
                            <line x1="12" y1="6" x2="12" y2="19"/>
                            <line x1="21" y1="6" x2="21" y2="19"/>
                        </svg>
                    </div>
                    <span class="text-lg font-medium text-gray-800">Sistema Contable</span>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-1">Iniciar sesión</h2>
                <p class="text-sm text-gray-500 mb-8">Ingresa tus credenciales para acceder</p>

                {{-- Estado de sesión (ej. "contraseña restablecida") --}}
                @if (session('status'))
                    <div class="mb-4 text-sm font-medium" style="color:#0F6E56;">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-5">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                                    <polyline points="3 7 12 13 21 7"/>
                                </svg>
                            </span>
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                   required autofocus autocomplete="username"
                                   placeholder="nombre@empresa.cl"
                                   class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm
                                          focus:outline-none focus:ring-2 focus:border-transparent"
                                   style="--tw-ring-color:#0F6E56;" />
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contraseña --}}
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                            <input id="password" name="password" type="password"
                                   required autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm
                                          focus:outline-none focus:ring-2 focus:border-transparent"
                                   style="--tw-ring-color:#0F6E56;" />
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Recordarme + olvidó contraseña --}}
                    <div class="flex items-center justify-between mb-6">
                        <label for="remember_me" class="flex items-center gap-2 text-sm text-gray-600">
                            <input id="remember_me" name="remember" type="checkbox"
                                   class="rounded border-gray-300"
                                   style="color:#0F6E56;">
                            Recordarme
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-sm hover:underline" style="color:#0F6E56;">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    {{-- Botón --}}
                    <button type="submit"
                            class="w-full text-white py-2.5 rounded-lg text-sm font-medium transition-opacity hover:opacity-90"
                            style="background-color:#0F6E56;">
                        Ingresar
                    </button>
                </form>

            </div>
        </div>

    </div>
</body>
</html>