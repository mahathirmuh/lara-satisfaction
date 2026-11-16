@extends('main.index')

@section('page-title', __('Survey Report'))

@push('breadcrumbs')
    <x-global.breadcrumb-item current>{{ $survey->title }}</x-global.breadcrumb-item>
@endpush

@push('style')
    <link rel="stylesheet" href="{{ asset('assets/libs/apexcharts/apexcharts.css') }}?v=3.40.0" />
    <style>
        #commentTable td.comment-cell {
            white-space: pre-line;
            min-width: 320px;
        }

        #departmentScoreTable tr.section-score-row td {
            border-top-style: dashed;
        }
    </style>
@endpush

@section('content')
    @php
        $exportParams = array_filter(['survey' => $survey, 'company_id' => $selectedCompanyId]);
    @endphp
    <div class="row g-3">
        {{-- Header & filter --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h2 class="mb-0">{{ $survey->title }}</h2>
                                <x-global.status-badge :color="$survey->status->color()" :label="$survey->status->label()" />
                            </div>
                            <div class="text-secondary small">
                                <i class="ti ti-calendar-event me-1"></i>{{ $survey->periodLabel() }}
                            </div>
                        </div>
                        <form method="GET" action="{{ route('survey-report.show', $survey) }}"
                            class="d-flex flex-wrap align-items-center gap-2">
                            <select name="company_id" class="form-select" style="min-width: 240px"
                                onchange="this.form.submit()" aria-label="{{ __('Filter by company') }}">
                                <option value="">{{ __('All companies') }}</option>
                                @foreach ($companies as $companyId => $companyName)
                                    <option value="{{ $companyId }}" @selected($selectedCompanyId === $companyId)>
                                        {{ $companyName }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($summary['is_reportable'])
                                @permission('survey-report', 'Read')
                                    <x-global.btn-detail :label="__('Export CSV')" color="green" size="sm"
                                        :href="route('survey-report.export', $exportParams)">
                                        <x-slot:icon><i class="ti ti-file-download"></i></x-slot:icon>
                                    </x-global.btn-detail>
                                @endpermission
                            @endif
                            <x-global.btn-detail label="" color="blue" size="sm" outline="true"
                                :href="route('survey-report.index')" data-bs-toggle="tooltip" data-bs-title="{{ __('Back') }}">
                                <x-slot:icon><i class="ti ti-arrow-left"></i></x-slot:icon>
                            </x-global.btn-detail>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary --}}
        <div class="col-sm-6 col-xl-3">
            <x-global.summary-card :title="__('Responses')" :value="number_format($summary['responses'])" color="blue"
                :subtitle="__(':count written comments', ['count' => number_format($summary['comments'])])">
                <x-slot:icon><i class="ti ti-forms"></i></x-slot:icon>
            </x-global.summary-card>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-global.summary-card :title="__('Participation Rate')"
                :value="$summary['participation_rate'] === null ? '—' : $summary['participation_rate'] . '%'" color="purple"
                :subtitle="__(':participants of :eligible active employees', [
                    'participants' => number_format($summary['participants']),
                    'eligible' => number_format($summary['eligible']),
                ])">
                <x-slot:icon><i class="ti ti-users-group"></i></x-slot:icon>
            </x-global.summary-card>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-global.summary-card :title="__('Service Quality')"
                :value="$summary['average'] === null ? '—' : number_format($summary['average'], 2) . ' / 5'" color="green"
                :subtitle="__(':count ratings · criticality :criticality', [
                    'count' => number_format($summary['ratings']),
                    'criticality' => $summary['criticality'] === null ? '—' : number_format($summary['criticality'], 2),
                ])">
                <x-slot:icon><i class="ti ti-star"></i></x-slot:icon>
            </x-global.summary-card>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-global.summary-card title="CSAT" :value="$summary['csat'] === null ? '—' : $summary['csat'] . '%'"
                color="orange" :subtitle="__('Share of quality ratings ≥ :threshold', ['threshold' => config('survey.satisfied_threshold')])">
                <x-slot:icon><i class="ti ti-mood-happy"></i></x-slot:icon>
            </x-global.summary-card>
        </div>

        @unless ($summary['is_reportable'])
            <div class="col-12">
                <div class="alert alert-warning mb-0" role="alert">
                    <div class="d-flex gap-2">
                        <i class="ti ti-shield-lock fs-2"></i>
                        <div>
                            <strong>{{ __('Results hidden to protect anonymity.') }}</strong>
                            {{ __('Scores and comments are shown once this selection has at least :min responses (currently :count).', [
                                'min' => $summary['min_group_size'],
                                'count' => $summary['responses'],
                            ]) }}
                        </div>
                    </div>
                </div>
            </div>
        @endunless

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
                        <h4 class="mb-0">{{ __('Service Quality Distribution') }}</h4>
                    </div>
                    <div class="card-body">
                        <div id="chartRatingDistribution"></div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="card">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0">{{ __('Participation Trend') }}</h4>
                </div>
                <div class="card-body">
                    <div id="chartParticipation"></div>
                </div>
            </div>
        </div>

        @if ($summary['is_reportable'])
            {{-- Department scores with their sections --}}
            <div class="col-12">
                <div class="card overflow-hidden">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0">{{ __('Department & Section Scores') }}</h4>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-vcenter mdka-border-gray-100 text-nowrap mb-0" id="departmentScoreTable">
                            <thead>
                                <tr class="mdka-bg-gray-50">
                                    <th class="text-capitalize fs-5 py-3">{{ __('Department / Section') }}</th>
                                    <th class="text-capitalize fs-5 py-3 text-center">{{ __('Evaluations') }}</th>
                                    <th class="text-capitalize fs-5 py-3 text-center">{{ __('Criticality') }}</th>
                                    <th class="text-capitalize fs-5 py-3 text-center">{{ __('Service Quality') }}</th>
                                    <th class="text-capitalize fs-5 py-3 text-center">CSAT</th>
                                    <th class="text-capitalize fs-5 py-3" width="30%">{{ __('Quality Distribution') }} (1 &rarr; 5)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($departmentScores as $row)
                                    <tr class="fw-medium">
                                        <td>
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span
                                                    class="avatar avatar-xs rounded mdka-bg-{{ $row['color'] }}-100 mdka-text-{{ $row['color'] }}-600">
                                                    <i class="ti ti-{{ $row['icon'] ?: 'building' }}"></i>
                                                </span>
                                                {{ $row['name'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ number_format($row['evaluations']) }}</td>
                                        <td class="text-center">{{ $row['criticality'] === null ? '—' : number_format($row['criticality'], 2) }}</td>
                                        <td class="text-center"><x-global.score-badge :value="$row['average']" /></td>
                                        <td class="text-center">
                                            <x-global.score-badge :value="$row['csat']" type="percentage" suffix="%" />
                                        </td>
                                        <td><x-global.rating-distribution :distribution="$row['distribution']" /></td>
                                    </tr>
                                    @foreach ($sectionScores->where('department_id', $row['department_id']) as $sectionRow)
                                        <tr class="section-score-row">
                                            <td class="ps-5 text-secondary">
                                                <i class="ti ti-corner-down-right me-1 mdka-text-{{ $row['color'] }}-600"></i>
                                                {{ $sectionRow['name'] }}
                                            </td>
                                            <td class="text-center text-secondary">{{ number_format($sectionRow['evaluations']) }}</td>
                                            @if ($sectionRow['is_hidden'])
                                                <td colspan="4" class="text-secondary small">
                                                    <i class="ti ti-shield-lock me-1"></i>{{ __('Hidden: fewer than :count employees evaluated this section.', ['count' => $summary['min_group_size']]) }}
                                                </td>
                                            @else
                                                <td class="text-center text-secondary">{{ $sectionRow['criticality'] === null ? '—' : number_format($sectionRow['criticality'], 2) }}</td>
                                                <td class="text-center"><x-global.score-badge :value="$sectionRow['average']" /></td>
                                                <td class="text-center">
                                                    <x-global.score-badge :value="$sectionRow['csat']" type="percentage" suffix="%" />
                                                </td>
                                                <td><x-global.rating-distribution :distribution="$sectionRow['distribution']" /></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Question breakdown --}}
            <div class="col-12">
                <div class="card overflow-hidden">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0">{{ __('Question Breakdown') }}</h4>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-vcenter mdka-border-gray-100 mb-0">
                            <thead>
                                <tr class="mdka-bg-gray-50">
                                    <th class="text-capitalize fs-5 py-3 text-center" width="40">#</th>
                                    <th class="text-capitalize fs-5 py-3">{{ __('Question') }}</th>
                                    <th class="text-capitalize fs-5 py-3">{{ __('Department / Section') }}</th>
                                    <th class="text-capitalize fs-5 py-3 text-center">{{ __('Answers') }}</th>
                                    <th class="text-capitalize fs-5 py-3 text-center">{{ __('Average') }}</th>
                                    <th class="text-capitalize fs-5 py-3" width="25%">{{ __('Distribution') }} (1 &rarr; 5)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($questionBreakdown as $row)
                                    <tr>
                                        <td class="text-center">{{ $row['number'] }}</td>
                                        <td style="min-width: 280px">{{ $row['question'] }}</td>
                                        <td class="text-nowrap">
                                            {{ $row['department'] }}
                                            @if ($row['section'])
                                                <div class="text-secondary small">
                                                    <i class="ti ti-corner-down-right me-1"></i>{{ $row['section'] }}
                                                </div>
                                            @endif
                                        </td>
                                        @if ($row['scale'])
                                            <td class="text-center">{{ number_format($row['ratings']) }}</td>
                                            <td class="text-center">
                                                <x-global.score-badge :value="$row['average']" />
                                                <div class="text-secondary small mt-1">{{ $row['type'] === 'criticality' ? __('Criticality') : __('Quality') }}</div>
                                            </td>
                                            <td><x-global.rating-distribution :distribution="$row['distribution']" :labels="$row['scale']" /></td>
                                        @else
                                            <td class="text-center">{{ number_format($row['comments']) }}</td>
                                            <td class="text-center"><span class="badge bg-azure-lt">{{ __('Open text') }}</span></td>
                                            <td class="text-secondary small">{{ __('See comments below') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Company breakdown --}}
            @if ($companyBreakdown->isNotEmpty())
                <div class="col-12">
                    <div class="card overflow-hidden">
                        <div class="card-header p-2 px-3">
                            <h4 class="mb-0">{{ __('Company Breakdown') }}</h4>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-vcenter mdka-border-gray-100 text-nowrap mb-0">
                                <thead>
                                    <tr class="mdka-bg-gray-50">
                                        <th class="text-capitalize fs-5 py-3">{{ __('Company') }}</th>
                                        <th class="text-capitalize fs-5 py-3 text-center">{{ __('Responses') }}</th>
                                        <th class="text-capitalize fs-5 py-3 text-center">{{ __('Average') }}</th>
                                        <th class="text-capitalize fs-5 py-3 text-center">CSAT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($companyBreakdown as $row)
                                        <tr>
                                            <td>{{ $row['company'] }}</td>
                                            <td class="text-center">{{ number_format($row['responses']) }}</td>
                                            <td class="text-center"><x-global.score-badge :value="$row['average']" /></td>
                                            <td class="text-center">
                                                <x-global.score-badge :value="$row['csat']" type="percentage" suffix="%" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Comments --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0"><i class="ti ti-message-2 me-1"></i> {{ __('Anonymous Comments') }}</h4>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <select id="commentDepartment" class="form-select" style="max-width: 240px"
                                    aria-label="{{ __('Filter comments by department') }}">
                                    <option value="">{{ __('All departments') }}</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                    <option value="general">{{ __('General') }}</option>
                                </select>
                                <div style="max-width: 280px" class="flex-grow-1">
                                    <x-global.input-search id="commentSearch" :placeholder="__('Search comments…')" />
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card overflow-hidden mdka-border-gray-200">
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-vcenter mdka-border-gray-100 mb-0" id="commentTable">
                                            <thead>
                                                <tr class="mdka-bg-gray-50">
                                                    <th class="text-capitalize fs-5 py-3 text-center" width="40">{{ __('No') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Department') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Question') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Comment') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="pagination-container mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('javascript')
    @include('main.components.js.charts')
    <script>
        $(function() {
            const departmentScores = @json($departmentScores);
            const ratingScale = @json($ratingScale);

            renderDepartmentScoreChart('#chartDepartmentScores', departmentScores);
            renderRatingDistributionChart('#chartRatingDistribution', departmentScores, ratingScale);
            renderParticipationTrendChart('#chartParticipation', @json($trend));
        });
    </script>

    @if ($summary['is_reportable'])
        <script>
            (function() {
                const urlComments = @json(route('survey-report.comments', $survey));
                const companyId = @json($selectedCompanyId);
                let currentPage = 1;
                let limit = 10;

                const columns = [
                    'index',
                    {
                        key: 'department',
                        className: 'text-nowrap',
                        render: (item) => `<span class="badge mdka-bg-${escapeHtml(item.color)}-100 mdka-text-${escapeHtml(item.color)}-600">${escapeHtml(item.department)}</span>` +
                            (item.section ? `<div class="text-secondary small mt-1">${escapeHtml(item.section)}</div>` : ''),
                    },
                    {
                        key: 'question',
                        className: 'text-secondary',
                    },
                    {
                        key: 'comment',
                        className: 'comment-cell',
                    },
                ];

                function loadComments() {
                    renderTableWithFeatures({
                        container: '#commentTable',
                        columns,
                        loading: true
                    });

                    $.ajax({
                        url: urlComments,
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            page: currentPage,
                            paginated: limit,
                            department_id: $('#commentDepartment').val() || null,
                            search: $('#commentSearch').val() || null,
                            company_id: companyId,
                        },
                        success: (response) => {
                            renderTableWithFeatures({
                                container: '#commentTable',
                                data: response.data,
                                columns,
                                currentPage,
                                limit,
                                meta: response.meta,
                                emptyText: @json(__('No comments found.')),
                                onPageChange: (page) => {
                                    currentPage = page;
                                    loadComments();
                                },
                                onLimitChange: (newLimit) => {
                                    limit = newLimit;
                                    currentPage = 1;
                                    loadComments();
                                },
                            });
                        },
                        error: (xhr) => renderTableWithFeatures({
                            container: '#commentTable',
                            columns,
                            emptyText: ajaxErrorMessages(xhr)[0],
                        }),
                    });
                }

                $('#commentDepartment').on('change', () => {
                    currentPage = 1;
                    loadComments();
                });

                $('#commentSearch').on('input', debounce(() => {
                    currentPage = 1;
                    loadComments();
                }, 400));

                loadComments();
            })();
        </script>
    @endif
@endpush
