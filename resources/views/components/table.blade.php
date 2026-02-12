<div class="bg-white shadow rounded-lg overflow-hidden">

    {{-- Header (botón crear, filtros, etc) --}}
    @isset($header)
        <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
            {{ $header }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    {{ $thead }}
                </tr>
            </thead>

            <tbody class="divide-y">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    {{-- Footer (paginación futura) --}}
    @isset($footer)
        <div class="p-4 border-t bg-gray-50">
            {{ $footer }}
        </div>
    @endisset

</div>
