@extends('main.index')

@section('page-title', __('Dashboard'))

@push('style')
    <link rel="stylesheet" href="{{ asset('assets/libs/apexcharts/apexcharts.css') }}?v=3.40.0" />
@endpush

@section('content')
    <div class="row g-3">
        {{-- Welcome --}}
        <div class="col-12">
            <div class="card rounded-3 overflow-hidden">
                <div class="card-body">
                    <h2 class="mb-1">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</h2>
                    <div class="text-secondary">{{ config('merdeka.app_tagline') }} &middot;
                        {{ now()->translatedFormat(__('l, d F Y')) }}</div>
                </div>
            </div>
        </div>

        {{-- Open surveys --}}
        @if ($canTakeSurveys)
            <div class="col-12">
                <h3 class="mb-0">{{ __('Open Surveys') }}</h3>
            </div>
            @forelse ($openSurveys as $survey)
                <div class="col-md-6 col-xl-4">
                    @include('SurveyForm::partials.survey-card', ['survey' => $survey])
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <x-global.empty-state icon="clipboard-off" :title="__('No survey is open right now')"
                            :subtitle="__('You will be notified when the next operational audit opens.')" />
                    </div>
                </div>
            @endforelse
        @endif

        {{-- Analytics --}}
        @if ($canViewReports && $featuredSurvey)
            <div class="col-12 mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h3 class="mb-0">{{ __('Survey Insights') }} &middot; {{ $featuredSurvey->title }}</h3>
                        <div class="text-secondary small">
                            {{ $featuredSurvey->periodLabel() }}
                            @if ($previousSurvey)
                                &middot; {{ __('compared with :title', ['title' => $previousSurvey->title]) }}
                            @endif
                        </div>
                    </div>
                    <x-global.btn-detail :label="__('Full Report')" color="blue" size="sm" outline="true"
                        :href="route('survey-report.show', $featuredSurvey)">
                        <x-slot:icon><i class="ti ti-report-analytics"></i></x-slot:icon>
                    </x-global.btn-detail>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Responses')" :value="number_format($summary['responses'])" color="blue"
                    :info="__('The number of people who sent the survey. Written comments counts every feedback they typed, one per section.')"
                    :subtitle="__(':count written comments', ['count' => number_format($summary['comments'])])">
                    <x-slot:icon><i class="ti ti-forms"></i></x-slot:icon>
                    <x-slot:trend>
                        <x-global.trend-indicator :base="$previousSummary['responses'] ?? null" :value="$summary['responses']" />
                    </x-slot:trend>
                </x-global.summary-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Participation Rate')"
                    :value="$summary['participation_rate'] === null ? '—' : $summary['participation_rate'] . '%'"
                    color="purple" :info="__('The share of the employees of the assessed departments who sent the survey.')"
                    :subtitle="__(':participants of :eligible employees', ['participants' => number_format($summary['participants']), 'eligible' => number_format($summary['eligible'])])">
                    <x-slot:icon><i class="ti ti-users-group"></i></x-slot:icon>
                    <x-slot:trend>
                        <x-global.trend-indicator :base="$previousSummary['participation_rate'] ?? null" :value="$summary['participation_rate']"
                            type="percentage" />
                    </x-slot:trend>
                </x-global.summary-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Service Quality')"
                    :value="$summary['average'] === null ? '—' : number_format($summary['average'], 2) . ' / 5'" color="green"
                    :info="__('The average of every Service Quality rating, from 1 (Very Poor) to 5 (Excellent).')"
                    :subtitle="__(':count quality ratings', ['count' => number_format($summary['ratings'])])">
                    <x-slot:icon><i class="ti ti-star"></i></x-slot:icon>
                    <x-slot:trend>
                        <x-global.trend-indicator :base="$previousSummary['average'] ?? null" :value="$summary['average']" />
                    </x-slot:trend>
                </x-global.summary-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card title="CSAT" :value="$summary['csat'] === null ? '—' : $summary['csat'] . '%'"
                    color="orange" :subtitle="__('Share of quality ratings ≥ :threshold', ['threshold' => config('survey.satisfied_threshold')])"
                    :info="__('Customer satisfaction: the share of Service Quality ratings of :threshold or more, out of 5.', ['threshold' => config('survey.satisfied_threshold')])">
                    <x-slot:icon><i class="ti ti-mood-happy"></i></x-slot:icon>
                    <x-slot:trend>
                        <x-global.trend-indicator :base="$previousSummary['csat'] ?? null" :value="$summary['csat']"
                            type="percentage" />
                    </x-slot:trend>
                </x-global.summary-card>
            </div>

            @if ($summary['is_reportable'])
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header p-2 px-3">
                            <h4 class="mb-0">{{ __('Service Quality by Department') }}</h4>
                        </div>
                        <div class="card-body">
                            <div id="chartDepartmentScores"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header p-2 px-3">
                            <h4 class="mb-0">{{ __('Department Ranking') }}</h4>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($departmentScores->sortByDesc('average')->values() as $row)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-bold text-secondary" style="width: 1.25rem">{{ $loop->iteration }}</span>
                                        <span
                                            class="avatar avatar-sm rounded mdka-bg-{{ $row['color'] }}-100 mdka-text-{{ $row['color'] }}-600">
                                            <i class="ti ti-{{ $row['icon'] ?: 'building' }}"></i>
                                        </span>
                                        <div class="flex-fill min-w-0">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-medium text-truncate">{{ $row['name'] }}</span>
                                                <x-global.score-badge :value="$row['average']" />
                                            </div>
                                            <x-global.rating-distribution :distribution="$row['distribution']" class="mt-1" />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="col-12">
                    <div class="alert alert-warning mb-0" role="alert">
                        <div class="d-flex gap-2">
                            <i class="ti ti-shield-lock fs-2"></i>
                            <div>
                                <strong>{{ __('Results hidden to protect anonymity.') }}</strong>
                                {{ __('Scores appear once at least :min responses are collected (currently :count).', ['min' => $summary['min_group_size'], 'count' => $summary['responses']]) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-lg-{{ $latestComments->isNotEmpty() ? 7 : 12 }}">
                <div class="card h-100">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0">{{ __('Participation Trend') }}</h4>
                    </div>
                    <div class="card-body">
                        <div id="chartParticipation"></div>
                    </div>
                </div>
            </div>
            @if ($latestComments->isNotEmpty())
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header p-2 px-3">
                            <h4 class="mb-0"><i class="ti ti-message-2 me-1"></i> {{ __('Comment Highlights') }}</h4>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($latestComments as $answer)
                                <div class="list-group-item">
                                    <span
                                        class="badge mdka-bg-{{ $answer->department?->color ?? 'gray' }}-100 mdka-text-{{ $answer->department?->color ?? 'gray' }}-600 mb-1">
                                        {{ $answer->department?->name ?? __('General') }}
                                    </span>
                                    <div class="text-secondary small mb-1">{{ $answer->question?->question }}</div>
                                    <div style="white-space: pre-line">{{ \Illuminate\Support\Str::limit($answer->comment, 220) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @elseif ($canViewReports)
            <div class="col-12">
                <div class="card">
                    <x-global.empty-state icon="chart-bar-off" :title="__('No survey results yet')"
                        :subtitle="__('Create and publish a survey period to start collecting feedback.')" />
                </div>
            </div>
        @endif
    </div>
@endsection

@if ($canViewReports && $featuredSurvey)
    @push('javascript')
        @include('main.components.js.charts')
        <script>
            $(function() {
                renderDepartmentScoreChart('#chartDepartmentScores', @json($departmentScores));
                renderParticipationTrendChart('#chartParticipation', @json($trend));
            });
        </script>
    @endpush
@endif
