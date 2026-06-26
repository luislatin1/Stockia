@php
    $patterns = (array) ($item['active'] ?? [($item['route'] ?? '')]);
    $isActive = false;
    foreach ($patterns as $pattern) {
        if (is_string($pattern) && $pattern !== '' && request()->routeIs($pattern)) {
            $isActive = true;
            break;
        }
    }

    $base    = 'w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors duration-150';
    $active  = 'bg-indigo-600 text-white font-medium';
    $normal  = 'text-gray-300 hover:bg-gray-800 hover:text-white';
    $classes = $base . ' ' . ($isActive ? $active : $normal);
@endphp

@if (($item['type'] ?? 'link') === 'logout')
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="{{ $classes }}">
            <span class="text-base leading-none">{{ $item['icon'] ?? '•' }}</span>
            <span>{{ $item['label'] ?? 'Salir' }}</span>
        </button>
    </form>
@else
    <a href="{{ route($item['route']) }}" class="{{ $classes }}">
        <span class="text-base leading-none">{{ $item['icon'] ?? '•' }}</span>
        <span>{{ $item['label'] }}</span>
    </a>
@endif
