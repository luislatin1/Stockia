<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

    @isset($header)
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-3 flex-wrap">
            {{ $header }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                    {{ $thead }}
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            {{ $footer }}
        </div>
    @endisset

</div>
