@props([
    'value' => null,
    'suffix' => '',
    'type' => 'average',
])

@php
    if ($value === null) {
        $color = 'secondary';
    } elseif ($type === 'percentage') {
        $color = $value >= 75 ? 'green' : ($value >= 50 ? 'yellow' : 'red');
    } else {
        $color = $value >= 4 ? 'green' : ($value >= 3 ? 'yellow' : 'red');
    }
@endphp

<span {{ $attributes->merge(['class' => 'badge bg-' . $color . '-lt fw-bold']) }}>
    {{ $value === null ? '—' : number_format((float) $value, $type === 'percentage' ? 1 : 2) . $suffix }}
</span>
