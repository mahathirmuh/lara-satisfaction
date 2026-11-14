@extends('main.index')

@section('page-title', 'Dashboard')

@push('style')
    <link rel="stylesheet" href="{{ asset('assets/libs/apexcharts/apexcharts.css') }}?v=3.40.0" />
@endpush

@section('content')
    <div class="row g-3">
        {{-- Welcome --}}
        <div class="col-12">
            <div class="card rounded-3 overflow-hidden">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-lg rounded-3 mdka-bg-brand-50 mdka-text-brand-500">
                            <i class="ti ti-message-heart fs-1"></i>
                        </span>
                        <div>
                            <h2 class="mb-1">Welcome back, {{ auth()->user()->name }}!</h2>
                            <div class="text-secondary">{{ config('merdeka.app_tagline') }} &middot;
                                {{ now()->format('l, d F Y') }}</div>
                        </div>
                    </div>
                    @if ($canTakeSurveys)
                        <x-global.btn-detail label="My Survey" color="blue" size="md" :href="route('survey-form.index')">
                            <x-slot:icon><i class="ti ti-clipboard-check"></i></x-slot:icon>
                        </x-global.btn-detail>
                    @endif
                </div>
            </div>
        </div>

        {{-- Open surveys --}}
        @if ($canTakeSurveys)
            <div class="col-12">
                <h3 class="mb-0">Open Surveys</h3>
            </div>
            @forelse ($openSurveys as $survey)
                <div class="col-md-6 col-xl-4">
                    @include('SurveyForm::partials.survey-card', ['survey' => $survey])
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <x-global.empty-state icon="clipboard-off" title="No survey is open right now"
                            subtitle="You will be notified when the next operational audit opens." />
                    </div>
                </div>
            @endforelse
        @endif

        {{-- Analytics --}}
        @if ($canViewReports && $featuredSurvey)
            <div class="col-12 mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h3 class="mb-0">Survey Insights &middot; {{ $featuredSurvey->title }}</h3>
                        <div class="text-secondary small">
                            {{ $featuredSurvey->periodLabel() }}
                            @if ($previousSurvey)
                                &middot; compared with {{ $previousSurvey->title }}
                            @endif
                        </div>
                    </div>
                    <x-global.btn-detail label="Full Report" color="blue" size="sm" outline="true"
                        :href="route('survey-report.show', $featuredSurvey)">
                        <x-slot:icon><i class="ti ti-report-analytics"></i></x-slot:icon>
                    </x-global.btn-detail>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card title="Responses" :value="number_format($summary['responses'])" color="blue"
                    :subtitle="number_format($summary['comments']) . ' written comments'">
                    <x-slot:icon><i class="ti ti-forms"></i></x-slot:icon>
                    <x-slot:trend>
                        <x-global.trend-indicator :base="$previousSummary['responses'] ?? null" :value="$summary['responses']" />
                    </x-slot:trend>
                </x-global.summary-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card title="Participation Rate"
                    :value="$summary['participation_rate'] === null ? '—' : $summary['participation_rate'] . '%'"
                    color="purple"
                    :subtitle="number_format($summary['participants']) . ' of ' . number_format($summary['eligible']) . ' employees'">
                    <x-slot:icon><i class="ti ti-users-group"></i></x-slot:icon>
                    <x-slot:trend>
                        <x-global.trend-indicator :base="$previousSummary['participation_rate'] ?? null" :value="$summary['participation_rate']"
                            type="percentage" />
                    </x-slot:trend>
                </x-global.summary-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card title="Service Quality"
                    :value="$summary['average'] === null ? '—' : number_format($summary['average'], 2) . ' / 5'" color="green"
                    :subtitle="number_format($summary['ratings']) . ' quality ratings'">
                    <x-slot:icon><i class="ti ti-star"></i></x-slot:icon>
                    <x-slot:trend>
                        <x-global.trend-indicator :base="$previousSummary['average'] ?? null" :value="$summary['average']" />
                    </x-slot:trend>
                </x-global.summary-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card title="CSAT" :value="$summary['csat'] === null ? '—' : $summary['csat'] . '%'"
                    color="orange" :subtitle="'Share of quality ratings ≥ ' . config('survey.satisfied_threshold')">
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
                            <h4 class="mb-0">Service Quality by Department</h4>
                        </div>
                        <div class="card-body">
                            <div id="chartDepartmentScores"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header p-2 px-3">
                            <h4 class="mb-0">Department Ranking</h4>
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
                                <strong>Results hidden to protect anonymity.</strong> Scores appear once at least
                                {{ $summary['min_group_size'] }} responses are collected (currently {{ $summary['responses'] }}).
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-lg-{{ $latestComments->isNotEmpty() ? 7 : 12 }}">
                <div class="card h-100">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0">Participation Trend</h4>
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
                            <h4 class="mb-0"><i class="ti ti-message-2 me-1"></i> Comment Highlights</h4>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($latestComments as $answer)
                                <div class="list-group-item">
                                    <span
                                        class="badge mdka-bg-{{ $answer->department?->color ?? 'gray' }}-100 mdka-text-{{ $answer->department?->color ?? 'gray' }}-600 mb-1">
                                        {{ $answer->department?->name ?? 'General' }}
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
                    <x-global.empty-state icon="chart-bar-off" title="No survey results yet"
                        subtitle="Create and publish a survey period to start collecting feedback." />
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
