<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Stockia') }} — @yield('title', 'Acceso')</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-700">

<div class="min-h-screen flex">

    {{-- ── Panel de marca (oculto en móvil) ───────────────────────── --}}
    <div class="hidden lg:flex flex-col justify-between w-1/2 bg-gray-900 text-white p-12">

        {{-- Wordmark --}}
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white text-xl leading-none select-none">
                {{ mb_strtoupper(mb_substr(config('app.name', 'S'), 0, 1)) }}
            </div>
            <span class="text-xl font-bold tracking-tight">{{ config('app.name', 'Stockia') }}</span>
        </div>

        {{-- Claim + features --}}
        <div class="max-w-sm">
            <h2 class="text-[2.15rem] font-bold leading-tight tracking-tight">
                Inventario y punto de venta para tu negocio.
            </h2>
            <ul class="mt-7 flex flex-col gap-3.5 text-sm text-gray-300">
                <li class="flex items-center gap-3">
                    <span class="text-lg w-6 text-center shrink-0">📦</span>
                    Inventario multi-almacén en tiempo real
                </li>
                <li class="flex items-center gap-3">
                    <span class="text-lg w-6 text-center shrink-0">🛒</span>
                    Punto de venta rápido y sin fricción
                </li>
                <li class="flex items-center gap-3">
                    <span class="text-lg w-6 text-center shrink-0">🧾</span>
                    Facturación electrónica (DTE) integrada
                </li>
            </ul>
        </div>

        <p class="text-xs text-gray-500 tracking-wide">El Salvador · DTE Ministerio de Hacienda · IVA 13%</p>
    </div>

    {{-- ── Área del formulario ─────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 bg-gray-100">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
        <p class="mt-5 text-xs text-gray-400 text-center">
            {{ config('app.name', 'Stockia') }} POS · ¿No tienes cuenta? Contacta a tu administrador.
        </p>
    </div>

</div>
</body>
</html>
