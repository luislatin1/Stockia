@props(['invalid' => false])

@php
$ring = $invalid
    ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-200';
@endphp

<input {{ $attributes->merge(['class' => "w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 {$ring}"]) }}>
