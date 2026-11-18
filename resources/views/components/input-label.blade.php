@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-on-surface']) }}>
    {{ $value ?? $slot }}
</label>