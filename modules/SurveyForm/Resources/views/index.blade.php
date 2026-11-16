@extends('main.index')

@section('page-title', __('My Survey'))

@section('content')
    <div class="row g-3">
        <div class="col-12">
            <div class="card mdka-bg-brand-500 text-white border-0 rounded-3">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <span class="avatar avatar-lg rounded-3 bg-white mdka-text-brand-500">
                        <i class="ti ti-message-heart fs-1"></i>
                    </span>
                    <div>
                        <h2 class="mb-1 text-white">{{ __(config('merdeka.app_tagline')) }}</h2>
                        <div class="text-white-50">
                            {{ __('Your answers are stored anonymously — they are never linked to your name, and results are only reported for groups of at least :count respondents.', [
                                'count' => config('survey.min_group_size'),
                            ]) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <h3 class="mb-0">{{ __('Open Surveys') }}</h3>
        </div>
        @forelse ($surveys as $survey)
            <div class="col-md-6 col-xl-4">
                @include('SurveyForm::partials.survey-card', ['survey' => $survey])
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <x-global.empty-state icon="clipboard-off" :title="__('No survey is open right now')"
                        :subtitle="__('You will be able to take part as soon as the next operational audit opens.')" />
                </div>
            </div>
        @endforelse

        <div class="col-12">
            <div class="card">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-history me-1"></i> {{ __('My Participation History') }}</h4>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap mb-0">
                        <thead>
                            <tr class="mdka-bg-gray-50">
                                <th class="text-capitalize fs-5 py-3 text-center" width="40">{{ __('No') }}</th>
                                <th class="text-capitalize fs-5 py-3">{{ __('Survey') }}</th>
                                <th class="text-capitalize fs-5 py-3">{{ __('Period') }}</th>
                                <th class="text-capitalize fs-5 py-3">{{ __('Submitted At') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $participation)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $participation->survey?->title }}</td>
                                    <td>{{ $participation->survey?->periodLabel() }}</td>
                                    <td>{{ $participation->submitted_at->translatedFormat(__('d M Y H:i')) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">{{ __('You have not completed any survey yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
