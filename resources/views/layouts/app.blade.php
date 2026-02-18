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
@php($isActive = fn (string $route): string => request()->routeIs($route) ? 'bg-gray-800' : '')

<nav class="flex-1 p-4 space-y-2 text-sm">

    {{-- GESTIÓN --}}
    <p class="text-gray-400 uppercase text-xs mt-4">Gestión</p>

    <a href="{{ route('dashboard') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('dashboard') }}">
        📊 Dashboard
    </a>
    
    {{-- COMERCIAL --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Comercial</p>

    @if (Route::has('sales.index') && in_array(currentRole(), ['Vendedor', 'Admin', 'SuperAdmin'], true))
    <a href="{{ route('sales.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('sales.*') }}">
        🧾 Ventas
    </a>
    @endif

    @if (Route::has('salesquotes.index') && in_array(currentRole(), ['Vendedor', 'Admin', 'SuperAdmin'], true))
    <a href="{{ route('salesquotes.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('salesquotes.*') }}">
        📑 Cotizaciones
    </a>
    @endif

    @if (Route::has('ptvpos.index') && in_array(currentRole(), ['Vendedor', 'Admin', 'SuperAdmin'], true))
    <a href="{{ route('ptvpos.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('ptvpos.index') }}">
        🧾 Resumen POS
    </a>
    <a href="{{ route('ptvpos.pos') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('ptvpos.pos') }}">
        🛒 Punto de Venta
    </a>
    <a href="{{ route('ptvpos.open') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('ptvpos.open') }}">
        🔓 Abrir Caja
    </a>
    <a href="{{ route('ptvpos.cash-movements.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('ptvpos.cash-movements.*') }}">
        💸 Movimientos Caja
    </a>
    <a href="{{ route('ptvpos.close') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('ptvpos.close') }}">
        🔒 Cerrar Caja
    </a>
    @if (Route::has('ptvpos.admin.registers.index') && in_array(currentRole(), ['Admin', 'SuperAdmin'], true))
    <a href="{{ route('ptvpos.admin.registers.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('ptvpos.admin.registers.*') }}">
        🧰 Cajas (Admin)
    </a>
    <a href="{{ route('ptvpos.admin.templates.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('ptvpos.admin.templates.*') }}">
        🧾 Plantillas POS
    </a>
    @endif
    @endif

    {{-- INVENTARIO --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Inventario</p>

    <a href="{{ route('products.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('products.*') }}">
        📦 Productos
    </a>

    <a href="{{ route('categories.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('categories.*') }}">
        🏷 Categorías
    </a>

    @if (Route::has('inventory_movements.index'))
    <a href="{{ route('inventory_movements.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('inventory_movements.*') }}">
        🔄 Movimientos
    </a>
    @endif

    @if (Route::has('core.admin.index'))
    <a href="{{ route('core.admin.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('core.admin.*') }}">
        🛠 Administración Central
    </a>
    @endif

    {{-- ADMINISTRACIÓN --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Administración</p>

    <a href="{{ route('users.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('users.*') }}">
        👤 Usuarios
    </a>

    <a href="{{ route('currencies.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('currencies.*') }}">
        💱 Monedas
    </a>
    @if(currentRole() === 'SuperAdmin')
    <p class="text-gray-400 uppercase text-xs mt-6">Configuración</p>

    <a href="{{ route('settings.modules.index') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('settings.modules.*') }}">
        ⚙️ Panel de Control
    </a>
    @endif

    {{-- SISTEMA --}}
    <p class="text-gray-400 uppercase text-xs mt-6">Sistema</p>

    <a href="{{ route('warehouse.select') }}"
       class="block px-3 py-2 rounded hover:bg-gray-800 {{ $isActive('warehouse.select') }}">
        🔁 Cambiar Almacén
    </a>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"
            class="w-full text-left px-3 py-2 rounded hover:bg-gray-800">
            🚪 Cerrar Sesión
        </button>
    </form>

</nav>


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
            @yield('content')
        </main>

    </div>

</div>

</body>
</html>
