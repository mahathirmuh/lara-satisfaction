@extends('main.index')

@section('page-title', 'Survey Report')

@section('content')
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header p-2 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="ti ti-chart-bar me-1"></i> Survey Periods</h4>
                    <span class="text-secondary small">
                        <i class="ti ti-shield-lock me-1"></i>Results are shown for groups of at least
                        {{ config('survey.min_group_size') }} responses.
                    </span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap mb-0">
                        <thead>
                            <tr class="mdka-bg-gray-50">
                                <th class="text-capitalize fs-5 py-3 text-center" width="40">No</th>
                                <th class="text-capitalize fs-5 py-3">Survey</th>
                                <th class="text-capitalize fs-5 py-3">Period</th>
                                <th class="text-capitalize fs-5 py-3 text-center">Status</th>
                                <th class="text-capitalize fs-5 py-3 text-center">Responses</th>
                                <th class="text-capitalize fs-5 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($surveys as $survey)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="fw-medium">{{ $survey->title }}</td>
                                    <td>{{ $survey->periodLabel() }}</td>
                                    <td class="text-center">
                                        <x-global.status-badge :color="$survey->status->color()" :label="$survey->status->label()" />
                                    </td>
                                    <td class="text-center">{{ number_format($survey->responses_count) }}</td>
                                    <td class="text-center">
                                        <x-global.btn-detail label="View Report" color="blue" size="sm"
                                            :href="route('survey-report.show', $survey)">
                                            <x-slot:icon><i class="ti ti-report-analytics"></i></x-slot:icon>
                                        </x-global.btn-detail>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-global.empty-state icon="chart-bar-off" title="No survey results yet"
                                            subtitle="Results appear here once a survey has been published." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
