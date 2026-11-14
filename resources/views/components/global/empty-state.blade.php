@props([
    'icon' => 'mood-empty',
    'title' => 'No data found',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'empty py-5']) }}>
    <div class="empty-icon">
        <i class="ti ti-{{ $icon }}" style="font-size: 3rem"></i>
    </div>
    <p class="empty-title">{{ $title }}</p>
    @if ($subtitle)
        <p class="empty-subtitle text-secondary">{{ $subtitle }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="empty-action">{{ $slot }}</div>
    @endif
</div>
