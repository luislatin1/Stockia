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

@if ($item['locked'] ?? false)
    @php
        $lockedTitle = 'Requiere el módulo “' . $item['module_label'] . '”. '
            . (currentRole() === 'SuperAdmin' ? 'Actívalo en Ajustes.' : 'Pide a un administrador que lo active.');
    @endphp
    @if (currentRole() === 'SuperAdmin')
        <a href="{{ route('settings.modules.index') }}" title="{{ $lockedTitle }}"
           class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-800/60 transition-colors duration-150">
            <span class="text-base leading-none opacity-50">{{ $item['icon'] ?? '•' }}</span>
            <span class="flex-1">{{ $item['label'] }}</span>
            <span class="text-xs" aria-hidden="true">🔒</span>
        </a>
    @else
        <div title="{{ $lockedTitle }}"
             class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-500 cursor-not-allowed">
            <span class="text-base leading-none opacity-50">{{ $item['icon'] ?? '•' }}</span>
            <span class="flex-1">{{ $item['label'] }}</span>
            <span class="text-xs" aria-hidden="true">🔒</span>
        </div>
    @endif
@elseif (($item['type'] ?? 'link') === 'logout')
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
