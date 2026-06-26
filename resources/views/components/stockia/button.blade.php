@props([
    'type'    => 'button',
    'variant' => 'primary',
    'size'    => 'md',
    'block'   => false,
])

@php
$variantClasses = match($variant) {
    'success'   => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500',
    'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'warning'   => 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-400',
    'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-indigo-500',
    'ghost'     => 'bg-transparent text-indigo-600 hover:bg-indigo-50 focus:ring-indigo-500',
    default     => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-xs',
    'lg' => 'px-5 py-3 text-base',
    default => 'px-4 py-2.5 text-sm',
};

$base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
$width = $block ? ' w-full' : '';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "{$base} {$variantClasses} {$sizeClasses}{$width}"]) }}
>
    {{ $slot }}
</button>
