<details class="group border-b border-gray-800/50 pb-2 last:border-b-0" @if (!empty($section['is_open'])) open @endif>
    <summary class="list-none cursor-pointer select-none text-gray-400 uppercase text-xs mt-2 flex items-center justify-between [&::-webkit-details-marker]:hidden">
        <span>{{ $section['label'] }}</span>
        <svg class="h-3 w-3 text-gray-500 transition-transform group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
    </summary>

    <div class="mt-2 space-y-1">
        @foreach ($section['items'] as $item)
            @include('layouts.partials.sidebar-item', ['item' => $item])
        @endforeach
    </div>
</details>
