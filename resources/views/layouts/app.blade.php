<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Stockia') }} — @yield('title', 'Panel')</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('head')
</head>

@php
    $uiCompany   = currentCompany();
    $uiSystemName = $uiCompany?->system_name ?: 'Stockia POS';
    $uiWarehouse  = session('current_warehouse_id')
        ? \App\Models\Warehouse::find(session('current_warehouse_id'))
        : null;
    $dteModuleEnabled = \Illuminate\Support\Facades\Schema::hasTable('modules')
        ? \App\Models\Module::where('key', 'dte-sv-mh')->where('enabled', true)->exists()
        : false;
@endphp

<body class="bg-gray-100 font-sans antialiased text-gray-700">

<div class="flex min-h-screen">

    {{-- ── SIDEBAR ─────────────────────────────────────────────────── --}}
    <aside class="w-64 bg-gray-900 text-gray-200 flex flex-col shrink-0">

        {{-- Wordmark --}}
        <div class="px-4 py-4 border-b border-gray-800 flex items-center gap-3">
            @if ($uiCompany?->logo_path)
                <img
                    src="{{ Storage::disk('public')->url($uiCompany->logo_path) }}"
                    alt="Logo"
                    class="h-9 w-9 rounded-lg bg-white object-contain p-1 shrink-0"
                >
            @else
                <div class="h-9 w-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-base shrink-0 select-none">
                    S
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-[10px] uppercase tracking-widest text-gray-500 leading-none mb-0.5">Sistema</p>
                <p class="text-sm font-semibold text-white truncate leading-tight">{{ $uiSystemName }}</p>
            </div>
        </div>

        {{-- Nav --}}
        @include('layouts.partials.sidebar', ['dteModuleEnabled' => $dteModuleEnabled])

        {{-- Footer --}}
        <div class="px-4 py-3 border-t border-gray-800 space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] text-gray-600">Stockia &copy; {{ date('Y') }}</span>
                <span id="sidebar-clock" class="text-[11px] text-gray-400 tabular-nums font-mono"></span>
            </div>
            <p id="sidebar-date" class="text-[11px] text-gray-600 capitalize"></p>
        </div>
    </aside>

    {{-- ── MAIN AREA ───────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Topbar --}}
        <header class="bg-white border-b border-gray-200 shadow-sm px-6 py-3 flex items-center justify-between gap-4 shrink-0">
            <h1 class="text-xl font-semibold text-gray-900 truncate">@yield('title')</h1>

            <div class="flex items-center gap-3 shrink-0">
                @yield('topbar-actions')

                {{-- Contexto: empresa + almacén --}}
                @if($uiWarehouse || $uiCompany)
                    <div class="hidden sm:flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-600 leading-none">
                        @if($uiCompany)
                            <span class="text-gray-400">🏢</span>
                            <span class="font-medium text-gray-700">{{ $uiCompany->name }}</span>
                        @endif
                        @if($uiWarehouse)
                            @if($uiCompany)<span class="text-gray-300 select-none">·</span>@endif
                            <span class="text-gray-400">📦</span>
                            <span class="font-medium text-gray-700">{{ $uiWarehouse->name }}</span>
                        @endif
                    </div>
                @endif

                {{-- Usuario + avatar --}}
                <div class="flex items-center gap-2">
                    <p class="hidden sm:block text-sm font-medium text-gray-800 leading-none">{{ auth()->user()->name }}</p>
                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 font-semibold text-sm flex items-center justify-center select-none uppercase shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash alerts --}}
        @if (session('success') || session('error') || session('warning') || session('info'))
            <div class="px-6 pt-4 space-y-2">
                @if (session('success'))
                    <div class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <span class="mt-0.5 shrink-0">✅</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <span class="mt-0.5 shrink-0">❌</span>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <span class="mt-0.5 shrink-0">⚠️</span>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if (session('info'))
                    <div class="flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        <span class="mt-0.5 shrink-0">ℹ️</span>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Main content --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

    </div>

</div>

@yield('scripts')
<script>
(function () {
    const clockEl = document.getElementById('sidebar-clock');
    const dateEl  = document.getElementById('sidebar-date');
    if (!clockEl) return;

    function tick() {
        const now  = new Date();
        clockEl.textContent = now.toLocaleTimeString('es-SV', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        dateEl.textContent  = now.toLocaleDateString('es-SV', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
</body>
</html>
