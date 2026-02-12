@props([
    'name',
    'options' => [],
    'selected' => null
])

<select
    name="{{ $name }}"
    id="{{ $name }}"
    {{ $attributes->merge([
        'class' => 'w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500'
    ]) }}
>
    @foreach($options as $value => $label)
        <option value="{{ $value }}"
            @selected(old($name, $selected) == $value)>
            {{ $label }}
        </option>
    @endforeach
</select>

<x-form.error :for="$name" />
