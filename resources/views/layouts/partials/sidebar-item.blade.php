@php
    $patterns = (array) ($item['active'] ?? [($item['route'] ?? '')]);
    $isActive = false;

    foreach ($patterns as $pattern) {
        if (is_string($pattern) && $pattern !== '' && request()->routeIs($pattern)) {
            $isActive = true;
            break;
        }
    }

    $baseClass = 'w-full flex items-center gap-2 px-3 py-2 rounded transition';
    $stateClass = $isActive ? 'bg-gray-800 text-white' : 'text-gray-200 hover:bg-gray-800/80';
@endphp

@if (($item['type'] ?? 'link') === 'logout')
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="{{ $baseClass }} {{ $stateClass }}">
            <span>{{ $item['icon'] ?? '•' }}</span>
            <span>{{ $item['label'] ?? 'Salir' }}</span>
        </button>
    </form>
@else
    <a href="{{ route($item['route']) }}" class="{{ $baseClass }} {{ $stateClass }}">
        <span>{{ $item['icon'] ?? '•' }}</span>
        <span>{{ $item['label'] }}</span>
    </a>
@endif
