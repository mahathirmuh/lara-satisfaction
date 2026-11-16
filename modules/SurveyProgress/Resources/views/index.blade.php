@extends('main.index')

@section('page-title', __('Survey Progress'))

@section('content')
    <div class="row g-3">
        @if ($survey === null)
            <div class="col-12">
                <div class="card">
                    <x-global.empty-state icon="clipboard-off" :title="__('No survey yet')"
                        :subtitle="__('The progress of employees appears here once a survey is published.')" />
                </div>
            </div>
        @else
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <form method="GET" action="{{ route('survey-progress.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                            <label for="progressSurvey" class="form-label mb-0 fw-medium">{{ __('Survey') }}</label>
                            <select id="progressSurvey" name="survey" class="form-select" onchange="this.form.submit()">
                                @foreach ($surveys as $item)
                                    <option value="{{ $item->id }}" @selected($item->id === $survey->id)>
                                        {{ $item->title }} ({{ $item->periodLabel() }}) · {{ $item->status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        @if ($canSendReminders && $survey->isOpen())
                            <x-global.btn-detail :label="__('Send Reminder')" color="blue" size="sm" outline="true"
                                :href="route('survey-management.reminder.index')">
                                <x-slot:icon><i class="ti ti-send"></i></x-slot:icon>
                            </x-global.btn-detail>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Employees')" :value="$summary['eligible']" icon="users" color="blue"
                    :subtitle="__('Accounts that fill in surveys')" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Not Started')" :value="$summary['not_started']" icon="circle-dashed"
                    color="yellow" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('In Progress')" :value="$summary['in_progress']" icon="loader" color="purple"
                    :subtitle="__(':count saved as draft', ['count' => $summary['saved_draft']])" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Completed')" :value="$summary['completed']" icon="circle-check" color="green"
                    :subtitle="$summary['completion_rate'] === null ? null : __(':rate% completed', ['rate' => $summary['completion_rate']])" />
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <input type="text" id="progressSearch" class="form-control rounded-3 w-auto" autocomplete="off"
                                    placeholder="{{ __('Search name, email, employee ID or job title…') }}" style="min-width: 280px" />
                                <select id="progressStatusFilter" class="form-select w-auto" aria-label="{{ __('Status') }}">
                                    <option value="">{{ __('All status') }}</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select id="progressCompanyFilter" class="form-select w-auto" aria-label="{{ __('Company') }}">
                                    <option value="">{{ __('All companies') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->code }} - {{ $company->name }}</option>
                                    @endforeach
                                </select>
                                <x-global.btn-detail label="" color="red" size="sm" outline="true" id="progressResetButton"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Reset') }}">
                                    <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                                </x-global.btn-detail>
                            </div>
                            <div class="col-12">
                                <div class="card overflow-hidden mdka-border-gray-200">
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-vcenter mdka-border-gray-100 text-nowrap" id="progressTable">
                                            <thead>
                                                <tr class="mdka-bg-gray-50">
                                                    <th class="text-capitalize fs-5 py-3 text-center" width="30">{{ __('No') }}</th>
                                                    <th class="text-capitalize fs-5 py-3" data-key="name">{{ __('Name') }}</th>
                                                    <th class="text-capitalize fs-5 py-3" data-key="employee_id">{{ __('Employee ID') }}</th>
                                                    <th class="text-capitalize fs-5 py-3" data-key="job_title">{{ __('Job Title') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Company') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Status') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Last Update') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="pagination-container mt-2"></div>
                            </div>
                            <div class="col-12 text-secondary small">
                                <i class="ti ti-shield-lock me-1"></i>
                                {{ __('Only the filling status is shown: answers stay anonymous and are never linked to a person. Only people with an account in this application are listed (created at their first sign-in or in User Management).') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('javascript')
    @if ($survey !== null)
        <script>
            const progressSurveyId = @json($survey->id);
            const progressSavedDraft = @json(__('Saved as draft'));

            const progressTable = initTablePage({
                container: '#progressTable',
                url: @json(route('survey-progress.list')),
                defaultSort: 'name',
                columns: [
                    'index',
                    {
                        key: 'name',
                        sortable: true,
                        render: (item) => `<div class="fw-medium">${escapeHtml(item.name)}</div>
                            <div class="small text-secondary">${escapeHtml(item.email)}</div>`,
                    },
                    {
                        key: 'employee_id',
                        sortable: true,
                        render: (item) => escapeHtml(item.employee_id || '-'),
                    },
                    {
                        key: 'job_title',
                        sortable: true,
                        render: (item) => escapeHtml(item.job_title || '-'),
                    },
                    {
                        key: 'company',
                        render: (item) => escapeHtml(item.company || '-'),
                    },
                    {
                        key: 'status',
                        render: (item) => `<span class="status status-${escapeHtml(item.status_color)}"><span class="status-dot"></span>${escapeHtml(item.status_label)}</span>
                            ${item.saved_draft ? `<div class="small text-secondary mt-1"><i class="ti ti-device-floppy me-1"></i>${escapeHtml(progressSavedDraft)}</div>` : ''}`,
                    },
                    {
                        key: 'last_activity',
                        render: (item) => escapeHtml(item.last_activity || '-'),
                    },
                ],
                searchInput: '#progressSearch',
                resetButton: '#progressResetButton',
                filters: () => ({
                    survey_id: progressSurveyId,
                    status: $('#progressStatusFilter').val(),
                    company: $('#progressCompanyFilter').val() ? [$('#progressCompanyFilter').val()] : [],
                }),
                onReset: () => {
                    $('#progressStatusFilter').val('');
                    $('#progressCompanyFilter').val('');
                },
            });

            $(function() {
                $('#progressStatusFilter, #progressCompanyFilter').on('change', () => progressTable.applyFilters());
                progressTable.reload();
            });
        </script>
    @endif
@endpush
