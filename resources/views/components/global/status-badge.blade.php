@props([
    'color' => 'gray',
    'label' => '',
])

<span {{ $attributes->merge(['class' => 'status status-' . $color]) }}>
    <span class="status-dot"></span>
    {{ $label }}
</span>
