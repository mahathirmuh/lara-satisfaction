@props([
    'base' => null,
    'value' => null,
    'type' => 'number',
])

@php
    $change = null;

    if (is_numeric($base) && is_numeric($value)) {
        $change = $type === 'percentage'
            ? (float) $value - (float) $base
            : (((float) $value - (float) $base) / ((float) $base != 0.0 ? (float) $base : 1)) * 100;
    }
@endphp

@if ($change !== null && round($change, 2) != 0.0)
    @php($formattedChange = ($change > 0 ? '+' : '') . number_format($change, 1))
    <span {{ $attributes->merge(['class' => 'd-inline-flex align-items-center lh-1 ' . ($change > 0 ? 'text-green' : 'text-red')]) }}
        title="{{ __('Compared with the previous survey') }}" data-bs-toggle="tooltip">
        {{ $type === 'percentage' ? __(':change pt', ['change' => $formattedChange]) : $formattedChange . '%' }}
        <i class="ti {{ $change > 0 ? 'ti-arrow-up-right' : 'ti-arrow-down-right' }}"></i>
    </span>
@endif
