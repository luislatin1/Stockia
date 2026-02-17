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

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-gray-900 text-gray-200 flex flex-col">
        <div class="p-4 text-xl font-bold border-b border-gray-700">
            🧾 STOCKIA - POS SYS
        </div>
@php
function active($route) {
    return request()->routeIs($route) ? 'bg-gray-800' : '';
}
@endphp

<nav class="flex-1 p-4 space-y-2 text-sm">

    {{-- GESTIÓN --}}
    <p class="text-gray-400 uppercase text-xs mt-4">Gestión</p>

    <a href="{{ route('dashboard') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('dashboard') }}">
        📊 Dashboard
    </a>
    
    {{-- COMERCIAL --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Comercial</p>

    @if (Route::has('sales.index'))
    <a href="{{ route('sales.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('sales.*') }}">
        🧾 Ventas
    </a>
    @endif

    @if (Route::has('sales.create'))
    <a href="{{ route('sales.create') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('sales.create') }}">
        ➕ Nueva Venta
    </a>
    @endif

    {{-- INVENTARIO --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Inventario</p>

    <a href="{{ route('products.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('products.*') }}">
        📦 Productos
    </a>

    <a href="{{ route('categories.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('categories.*') }}">
        🏷 Categorías
    </a>

    @if (Route::has('inventory_movements.index'))
    <a href="{{ route('inventory_movements.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('inventory_movements.*') }}">
        🔄 Movimientos
    </a>
    @endif

    @if (Route::has('warehouses.index'))
    <a href="{{ route('warehouses.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('warehouses.*') }}">
        🏬 Bodegas
    </a>
    @endif

    {{-- ADMINISTRACIÓN --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Administración</p>

    <a href="{{ route('users.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('users.*') }}">
        👤 Usuarios
    </a>

    <a href="{{ route('companies.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('companies.*') }}">
        🏢 Empresas
    </a>

    <a href="{{ route('currencies.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('currencies.*') }}">
        💱 Monedas
    </a>
    @if(currentRole() === 'SuperAdmin')
    <p class="text-gray-400 uppercase text-xs mt-6">Configuración</p>

    <a href="{{ route('settings.modules.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ active('settings.modules.*') }}">
        ⚙️ Panel de Control
    </a>
    @endif

    {{-- SISTEMA --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Sistema</p>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"
            class="w-full text-left px-3 py-2 rounded hover:bg-gray-800">
            🚪 Cerrar Sesión
        </button>
    </form>

</nav>


        <div class="p-4 border-t border-gray-700 text-xs text-gray-400">
            Laravel POS v1.0
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
            @yield('content')
        </main>

    </div>

</div>

</body>
</html>
