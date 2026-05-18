<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@yield('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<body class="bg-gray-100">

@php($uiCompany = currentCompany())
@php($uiSystemName = $uiCompany?->system_name ?: 'Stockia POS')
@php($dteModuleEnabled = \Illuminate\Support\Facades\Schema::hasTable('modules')
    ? \App\Models\Module::where('key', 'dte-sv-mh')->where('enabled', true)->exists()
    : false)

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-gray-900 text-gray-200 flex flex-col">
        <div class="p-4 text-xl font-bold border-b border-gray-700">
            <div class="flex items-center gap-3">
                @if ($uiCompany?->logo_path)
                    <img src="{{ Storage::disk('public')->url($uiCompany->logo_path) }}" alt="Logo empresa" class="h-10 w-10 rounded bg-white object-contain p-1">
                @endif
                <div class="leading-tight">
                    <div class="text-xs uppercase tracking-wide text-gray-400">Sistema</div>
                    <div>{{ $uiSystemName }}</div>
                </div>
            </div>
        </div>
@include('layouts.partials.sidebar', ['dteModuleEnabled' => $dteModuleEnabled])


        <div class="p-4 border-t border-gray-700 text-xs text-gray-400">
            {{ $uiSystemName }}
        </div>
    </aside>

    <!-- CONTENT -->
    <div class="flex-1 flex flex-col">

        <!-- TOP BAR -->
        <header class="bg-white shadow p-4 flex justify-between items-center">
            <h1 class="text-xl font-semibold">@yield('title')</h1>
            <div class="text-sm text-gray-600">
                Usuario Activo: {{ auth()->user()->name }} 
                <br> 
                Almacen: {{$warehouseId = session('current_warehouse_id');}} |
                ID Usuario: {{ auth()->user()->id }} | ID Empresa: {{ session('current_company_id') }}
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="p-6 flex-1">
            @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif
            @if(session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        {{ session('error') }}
    </div>
@endif
            @if(session('warning'))
    <div class="bg-amber-100 text-amber-800 p-3 rounded mb-4">
        {{ session('warning') }}
    </div>
@endif
            @yield('content')
        </main>

    </div>

</div>

</body>
</html>
