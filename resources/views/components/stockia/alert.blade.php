@props(['tone' => 'info'])

@php
$classes = match($tone) {
    'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
    'danger'  => 'bg-red-50 border-red-200 text-red-800',
    'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
    default   => 'bg-blue-50 border-blue-200 text-blue-800',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm {$classes}"]) }}>
    {{ $slot }}
</div>
