@extends('main.index')

@section('page-title', __('Survey Report'))

@section('content')
    <div class="row g-3">
        @if ($noDepartmentMessage)
            <div class="col-12">
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="ti ti-info-circle me-1"></i>{{ $noDepartmentMessage }}
                </div>
            </div>
        @endif
        <div class="col-12">
            <div class="card">
                <div class="card-header p-2 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="ti ti-chart-bar me-1"></i> {{ __('Survey Periods') }}</h4>
                    <span class="text-secondary small">
                        <i class="ti ti-shield-lock me-1"></i>{{ __('Results are shown for groups of at least :count responses.', ['count' => config('survey.min_group_size')]) }}
                    </span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap mb-0">
                        <thead>
                            <tr class="mdka-bg-gray-50">
                                <th class="text-capitalize fs-5 py-3 text-center" width="40">{{ __('No') }}</th>
                                <th class="text-capitalize fs-5 py-3">{{ __('Survey') }}</th>
                                <th class="text-capitalize fs-5 py-3">{{ __('Period') }}</th>
                                <th class="text-capitalize fs-5 py-3 text-center">{{ __('Status') }}</th>
                                <th class="text-capitalize fs-5 py-3 text-center">{{ __('Responses') }}</th>
                                <th class="text-capitalize fs-5 py-3 text-center">{{ __('Action') }}</th>
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
                                        <x-global.btn-detail :label="__('View Report')" color="blue" size="sm"
                                            :href="route('survey-report.show', $survey)">
                                            <x-slot:icon><i class="ti ti-report-analytics"></i></x-slot:icon>
                                        </x-global.btn-detail>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-global.empty-state icon="chart-bar-off" :title="__('No survey results yet')"
                                            :subtitle="__('Results appear here once a survey has been published.')" />
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
