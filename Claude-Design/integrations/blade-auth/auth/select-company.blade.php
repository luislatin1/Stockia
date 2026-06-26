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
            <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Paso 1 de 2</span>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 tracking-tight">Selecciona tu empresa</h1>
            <p class="mt-1 text-sm text-gray-500">Tu cuenta tiene acceso a más de una empresa.</p>
        </div>

        @if ($companies->isEmpty())
            <x-stockia.alert tone="warning">
                No tienes empresas asignadas. Solicita acceso a un administrador.
            </x-stockia.alert>
        @else
            <form method="POST" action="{{ route('company.select') }}" class="space-y-5"
                  x-data="{ selected: '{{ old('company_id', $companies->first()->id) }}' }">
                @csrf

                {{-- Lista de opciones --}}
                <div class="flex flex-col gap-2.5">
                    @foreach ($companies as $company)
                        <button
                            type="button"
                            @click="selected = '{{ $company->id }}'"
                            :class="selected == '{{ $company->id }}'
                                ? 'border-indigo-600 ring-2 ring-indigo-100'
                                : 'border-gray-200 hover:border-gray-300'"
                            class="flex items-center gap-3 w-full text-left px-3.5 py-3 rounded-lg border bg-white text-sm transition cursor-pointer shadow-sm">
                            <span class="text-xl w-7 text-center shrink-0">🏢</span>
                            <span class="flex flex-col gap-0.5 min-w-0">
                                <span class="font-semibold text-gray-900">{{ $company->name }}</span>
                                @if ($company->tax_id ?? false)
                                    <span class="text-xs text-gray-400">NIT {{ $company->tax_id }}</span>
                                @endif
                            </span>
                            <span class="ml-auto text-indigo-600 font-bold shrink-0 transition"
                                  x-show="selected == '{{ $company->id }}'">✓</span>
                        </button>
                    @endforeach
                </div>

                <input type="hidden" name="company_id" :value="selected">

                <x-stockia.button type="submit" :block="true">
                    Continuar
                </x-stockia.button>

            </form>
        @endif

    </div>

</x-guest-layout>
