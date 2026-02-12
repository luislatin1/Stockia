@props([
    'name',
    'value' => ''
])

<textarea
    name="{{ $name }}"
    id="{{ $name }}"
    rows="4"
    {{ $attributes->merge([
        'class' => 'w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500'
    ]) }}
>{{ old($name, $value) }}</textarea>

<x-form.error :for="$name" />
