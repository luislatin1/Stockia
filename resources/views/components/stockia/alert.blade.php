@props(['tone' => 'info'])

@php
[$bg, $border, $text, $icon] = match($tone) {
    'success' => ['bg-emerald-50', 'border-emerald-200', 'text-emerald-800', '✅'],
    'danger'  => ['bg-red-50',     'border-red-200',     'text-red-800',     '❌'],
    'warning' => ['bg-amber-50',   'border-amber-200',   'text-amber-800',   '⚠️'],
    default   => ['bg-blue-50',    'border-blue-200',    'text-blue-800',    'ℹ️'],
};
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm {$bg} {$border} {$text}"]) }}>
    <span class="mt-0.5 shrink-0 text-base leading-none">{{ $icon }}</span>
    <div>{{ $slot }}</div>
</div>
