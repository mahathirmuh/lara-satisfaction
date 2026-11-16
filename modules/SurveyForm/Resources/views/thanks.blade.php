@extends('main.index')

@section('page-title', __('Thank You'))

@push('breadcrumbs')
    <x-global.breadcrumb-item>{{ $survey->title }}</x-global.breadcrumb-item>
    <x-global.breadcrumb-item current>{{ __('Thank You') }}</x-global.breadcrumb-item>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card rounded-3">
                <div class="card-status-top bg-green"></div>
                <x-global.empty-state icon="circle-check" :title="__('Thank you for your feedback!')"
                    :subtitle="__('Your answers for “:survey” have been recorded anonymously. They cannot be traced back to you and can no longer be changed.', ['survey' => $survey->title])">
                    <div class="d-flex justify-content-center mb-3">
                        @include('SurveyForm::partials.status-flow', [
                            'status' => \App\Enums\ParticipationStatus::Completed,
                            'savedDraft' => false,
                        ])
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <x-global.btn-detail :label="__('Back to My Survey')" color="blue" size="sm"
                            :href="route('survey-form.index')">
                            <x-slot:icon><i class="ti ti-clipboard-check"></i></x-slot:icon>
                        </x-global.btn-detail>
                        <x-global.btn-detail :label="__('Dashboard')" color="blue" size="sm" outline="true"
                            :href="route('dashboard.index')">
                            <x-slot:icon><i class="ti ti-dashboard"></i></x-slot:icon>
                        </x-global.btn-detail>
                    </div>
                </x-global.empty-state>
            </div>
        </div>
    </div>
@endsection

@push('javascript')
    <script>
        // The submission succeeded: drop the locally saved draft of this survey.
        try {
            const prefix = 'survey-draft:{{ $survey->id }}:';

            Object.keys(localStorage)
                .filter((key) => key.startsWith(prefix))
                .forEach((key) => localStorage.removeItem(key));
        } catch (error) {
            // Storage unavailable (private mode); nothing to clean up.
        }
    </script>
@endpush
