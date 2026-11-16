{{-- Card of an open survey with where the signed-in user stands: Not Started -> In Progress (opened the form, possibly with a saved draft) -> Completed. Expects $survey from SurveyFormInterface::availableFor() (questions_count, has_draft, participation_status). --}}
@php($status = $survey->participation_status)
@php($isCompleted = $status === \App\Enums\ParticipationStatus::Completed)
<div class="card h-100 rounded-3">
    <div class="card-status-top bg-{{ $status->color() }}"></div>
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <h3 class="card-title mb-0">{{ $survey->title }}</h3>
            <x-global.status-badge :color="$status->color()" :label="$status->label()" />
        </div>
        <div class="text-secondary small mb-3 d-flex flex-wrap align-items-center gap-2">
            <span><i class="ti ti-calendar-event me-1"></i>{{ $survey->periodLabel() }}</span>
            @if (! $isCompleted && $survey->isClosingSoon())
                <span class="badge {{ $survey->daysLeft() <= 3 ? 'bg-red-lt' : 'bg-orange-lt' }}">
                    <i class="ti ti-alarm me-1"></i>{{ $survey->deadlineLabel() }}
                </span>
            @endif
        </div>

        {{-- Where the person stands now --}}
        @include('SurveyForm::partials.status-flow', ['status' => $status, 'savedDraft' => (bool) $survey->has_draft, 'class' => 'mb-3'])

        @if ($survey->description)
            <p class="text-secondary mb-3">{{ \Illuminate\Support\Str::limit($survey->description, 160) }}</p>
        @endif
        <div class="d-flex flex-wrap gap-3 small text-secondary mt-auto mb-3">
            <span><i class="ti ti-clock me-1"></i>{{ __(':minutes min', ['minutes' => $survey->estimated_minutes]) }}</span>
            <span><i class="ti ti-list-check me-1"></i>{{ __(':count questions', ['count' => $survey->questions_count]) }}</span>
            <span><i class="ti ti-shield-lock me-1"></i>{{ __('Anonymous') }}</span>
        </div>
        @if ($isCompleted)
            <x-global.btn-detail :label="__('Thank you for participating')" color="green" size="sm" outline="true" disabled>
                <x-slot:icon><i class="ti ti-circle-check"></i></x-slot:icon>
            </x-global.btn-detail>
        @else
            @permission('survey-form', 'Create')
                <x-global.btn-detail :label="$status === \App\Enums\ParticipationStatus::InProgress ? __('Continue Survey') : __('Start Survey')"
                    color="blue" size="sm" :href="route('survey-form.show', $survey)" iconPosition="right">
                    <x-slot:icon><i class="ti ti-arrow-right"></i></x-slot:icon>
                </x-global.btn-detail>
            @endpermission
        @endif
    </div>
</div>
