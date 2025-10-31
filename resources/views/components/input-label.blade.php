@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-indigo-500']) }}>
    {{ $value ?? $slot }}
</label>
