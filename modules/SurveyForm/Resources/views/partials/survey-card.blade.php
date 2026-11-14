{{-- Card of an open survey with the signed-in user's completion status. Expects $survey (withCount questions, has_participated, has_draft). --}}
<div class="card h-100 rounded-3">
    <div class="card-status-top {{ $survey->has_participated ? 'bg-green' : ($survey->has_draft ? 'bg-azure' : 'bg-blue') }}"></div>
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <h3 class="card-title mb-0">{{ $survey->title }}</h3>
            @if ($survey->has_participated)
                <x-global.status-badge color="green" label="Completed" />
            @elseif ($survey->has_draft)
                <x-global.status-badge color="azure" label="Draft saved" />
            @else
                <x-global.status-badge color="yellow" label="Not started" />
            @endif
        </div>
        <div class="text-secondary small mb-3 d-flex flex-wrap align-items-center gap-2">
            <span><i class="ti ti-calendar-event me-1"></i>{{ $survey->periodLabel() }}</span>
            @if (! $survey->has_participated && $survey->isClosingSoon())
                <span class="badge {{ $survey->daysLeft() <= 3 ? 'bg-red-lt' : 'bg-orange-lt' }}">
                    <i class="ti ti-alarm me-1"></i>{{ $survey->deadlineLabel() }}
                </span>
            @endif
        </div>
        @if ($survey->description)
            <p class="text-secondary mb-3">{{ \Illuminate\Support\Str::limit($survey->description, 160) }}</p>
        @endif
        <div class="d-flex flex-wrap gap-3 small text-secondary mt-auto mb-3">
            <span><i class="ti ti-clock me-1"></i>{{ $survey->estimated_minutes }} min</span>
            <span><i class="ti ti-list-check me-1"></i>{{ $survey->questions_count }} questions</span>
            <span><i class="ti ti-shield-lock me-1"></i>Anonymous</span>
        </div>
        @if ($survey->has_participated)
            <x-global.btn-detail label="Thank you for participating" color="green" size="sm" outline="true" disabled>
                <x-slot:icon><i class="ti ti-circle-check"></i></x-slot:icon>
            </x-global.btn-detail>
        @else
            @permission('survey-form', 'Create')
                <x-global.btn-detail :label="$survey->has_draft ? 'Continue Survey' : 'Start Survey'" color="blue" size="sm"
                    :href="route('survey-form.show', $survey)" iconPosition="right">
                    <x-slot:icon><i class="ti ti-arrow-right"></i></x-slot:icon>
                </x-global.btn-detail>
            @endpermission
        @endif
    </div>
</div>
