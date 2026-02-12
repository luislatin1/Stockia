@props([
    'name',
    'type' => 'text',
    'value' => '',
])

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name }}"
    value="{{ old($name, $value) }}"
    {{ $attributes->merge([
        'class' => 'w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500'
    ]) }}
/>

<x-form.error :for="$name" />
