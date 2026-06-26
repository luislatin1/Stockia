<x-guest-layout>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-7 space-y-5">

        {{-- Errores --}}
        @if ($errors->any())
            <x-stockia.alert tone="danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </x-stockia.alert>
        @endif

        {{-- Encabezado --}}
        <div>
            <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Paso 2 de 2</span>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 tracking-tight">Selecciona tu almacén</h1>
            <p class="mt-1 text-sm text-gray-500">Elige el almacén con el que vas a trabajar hoy.</p>
        </div>

        @if ($warehouses->isEmpty())
            <x-stockia.alert tone="warning">
                No hay almacenes disponibles para esta empresa.
            </x-stockia.alert>
        @else
            <form method="POST" action="{{ route('warehouse.select') }}" class="space-y-5"
                  x-data="{ selected: '{{ old('warehouse_id', $warehouses->first()->id) }}' }">
                @csrf

                {{-- Lista de opciones --}}
                <div class="flex flex-col gap-2.5">
                    @foreach ($warehouses as $warehouse)
                        <button
                            type="button"
                            @click="selected = '{{ $warehouse->id }}'"
                            :class="selected == '{{ $warehouse->id }}'
                                ? 'border-indigo-600 ring-2 ring-indigo-100'
                                : 'border-gray-200 hover:border-gray-300'"
                            class="flex items-center gap-3 w-full text-left px-3.5 py-3 rounded-lg border bg-white text-sm transition cursor-pointer shadow-sm">
                            <span class="text-xl w-7 text-center shrink-0">🏬</span>
                            <span class="flex flex-col gap-0.5 min-w-0">
                                <span class="font-semibold text-gray-900">{{ $warehouse->name }}</span>
                                @if ($warehouse->location ?? false)
                                    <span class="text-xs text-gray-400">{{ $warehouse->location }}</span>
                                @endif
                            </span>
                            <span class="ml-auto text-indigo-600 font-bold shrink-0 transition"
                                  x-show="selected == '{{ $warehouse->id }}'">✓</span>
                        </button>
                    @endforeach
                </div>

                <input type="hidden" name="warehouse_id" :value="selected">

                <x-stockia.button type="submit" :block="true">
                    Entrar a Stockia
                </x-stockia.button>

                <a href="{{ route('company.select') }}"
                   class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors">
                    ← Cambiar empresa
                </a>

            </form>
        @endif

    </div>

</x-guest-layout>
