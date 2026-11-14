@props([
    'distribution' => [],
    'labels' => null,
])

@php
    $labels ??= \App\Enums\QuestionType::Rating->scaleLabels();
    $colors = [1 => 'red', 2 => 'orange', 3 => 'yellow', 4 => 'lime', 5 => 'green'];
    $total = array_sum($distribution);
@endphp

@if ($total > 0)
    <div {{ $attributes->merge(['class' => 'progress progress-separated']) }} style="height: .75rem; min-width: 140px">
        @foreach ($colors as $rating => $color)
            @php($count = (int) ($distribution[$rating] ?? 0))
            @if ($count > 0)
                <div class="progress-bar bg-{{ $color }}" role="progressbar"
                    style="width: {{ round($count / $total * 100, 2) }}%" data-bs-toggle="tooltip"
                    title="{{ $rating }} - {{ $labels[$rating] ?? '' }}: {{ $count }} ({{ round($count / $total * 100) }}%)"
                    aria-label="{{ $rating }}: {{ $count }}"></div>
            @endif
        @endforeach
    </div>
@else
    <span class="text-muted">&mdash;</span>
@endif
