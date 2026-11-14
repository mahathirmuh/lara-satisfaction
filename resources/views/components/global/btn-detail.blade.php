@props([
    'label' => '',
    'icon' => '',
    'disabled' => false,
    'size' => 'md',
    'color' => 'brand',
    'iconPosition' => 'left',
    'outline' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $sizeClass = match ($size) {
        'xs' => 'px-1 py-0',
        'sm' => 'px-2 py-1',
        'lg' => 'px-4 py-3',
        'xl' => 'px-5 py-4',
        default => 'px-3 py-2',
    };

    $colorClass = filter_var($outline, FILTER_VALIDATE_BOOLEAN)
        ? 'btn-outline-' . $color
        : 'mdka-btn-' . $color . '-500 text-white border-0';

    $hasIcon = filled((string) $icon);

    $classes = implode(' ', array_filter([
        'd-inline-flex align-items-center justify-content-center gap-2 btn btn-sm rounded-2',
        $sizeClass,
        $colorClass,
        $hasIcon && $label === '' ? 'btn-icon' : null,
        $iconPosition === 'left' ? 'flex-row-reverse' : 'flex-row',
    ]));
@endphp

@if ($href)
    <a {{ $attributes->merge(['class' => $classes . ($disabled ? ' disabled' : ''), 'href' => $href]) }}
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif>
        @if ($label !== '')
            <span>{{ $label }}</span>
        @endif
        {{ $icon }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        @if ($label !== '')
            <span>{{ $label }}</span>
        @endif
        {{ $icon }}
    </button>
@endif
