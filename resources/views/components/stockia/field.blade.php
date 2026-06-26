@props(['label' => '', 'for' => '', 'error' => null])

<div>
    @if($label)
        <label for="{{ $for }}" class="mb-1 block text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif

    {{ $slot }}

    @if($error)
        <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
