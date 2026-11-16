@props([
    'valueId' => null,
    'value' => '',
    'title' => '',
    'subtitle' => null,
    'icon' => '',
    'color' => 'gray',
    'size' => 'md',
    'trend' => '',
])

@php
    $palette = [
        'red' => ['mdka-text-red-600', 'mdka-bg-red-100', 'mdka-bg-red-300'],
        'blue' => ['mdka-text-blue-500', 'mdka-bg-blue-50', 'mdka-bg-blue-200'],
        'green' => ['mdka-text-green-600', 'mdka-bg-green-100', 'mdka-bg-green-300'],
        'yellow' => ['mdka-text-yellow-500', 'mdka-bg-yellow-50', 'mdka-bg-yellow-200'],
        'purple' => ['mdka-text-purple-600', 'mdka-bg-purple-100', 'mdka-bg-purple-300'],
        'pink' => ['mdka-text-pink-600', 'mdka-bg-pink-100', 'mdka-bg-pink-300'],
        'orange' => ['mdka-text-orange-600', 'mdka-bg-orange-100', 'mdka-bg-orange-300'],
        'lime' => ['mdka-text-lime-600', 'mdka-bg-lime-100', 'mdka-bg-lime-300'],
        'brand' => ['mdka-text-brand-600', 'mdka-bg-brand-100', 'mdka-bg-brand-300'],
        'gray' => ['mdka-text-gray-600', 'mdka-bg-gray-100', 'mdka-bg-gray-300'],
    ];

    [$textColor, $bgColor, $clipColor] = $palette[$color] ?? $palette['gray'];

    [$valueSize, $titleSize, $cardPadding] = match ($size) {
        'sm' => ['1em', '0.8em', 'p-2'],
        'lg' => ['2em', '1.25em', 'p-3'],
        default => ['1.5em', '1em', 'p-3'],
    };
@endphp

@pushOnce('style')
    <style>
        .summary-icon {
            position: relative;
            width: 50px;
            height: 50px;
            flex: 0 0 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            font-size: 1.5rem;
        }

        .summary-icon-clip {
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 25%;
            right: -5px;
            bottom: -5px;
        }
    </style>
@endPushOnce

<div {{ $attributes->merge(['class' => 'card h-100 rounded-3']) }}>
    <div class="card-body {{ $cardPadding }}">
        <div class="d-flex h-100 w-100 justify-content-start align-items-center gap-4">
            <div class="summary-icon rounded-3 {{ $textColor }} {{ $bgColor }}">
                {{-- The icon: markup given as a slot (<x-slot:icon>), or the name of a Tabler icon (icon="users"). --}}
                @if ($icon instanceof \Illuminate\Contracts\Support\Htmlable)
                    {{ $icon }}
                @elseif (filled($icon))
                    <i class="ti ti-{{ $icon }}" aria-hidden="true"></i>
                @endif
                <span class="summary-icon-clip {{ $clipColor }}"></span>
            </div>
            <div class="d-flex flex-column position-relative min-w-0">
                <span class="fw-medium text-truncate" style="font-size: {{ $titleSize }}">{{ $title }}</span>
                <div class="d-flex align-items-center justify-content-start gap-2">
                    <span @if ($valueId) id="{{ $valueId }}" @endif class="fw-bolder"
                        style="font-size: {{ $valueSize }}">{{ $value }}</span>
                    @if (filled((string) $trend))
                        <span class="small">{{ $trend }}</span>
                    @endif
                </div>
                @if ($subtitle)
                    <span class="text-muted small text-truncate">{{ $subtitle }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
