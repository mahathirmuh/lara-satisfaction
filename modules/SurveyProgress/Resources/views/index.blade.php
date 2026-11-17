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
                        @if ($canSendReminders)
                            <x-global.btn-detail :label="__('Send Reminder')" color="blue" size="sm" outline="true"
                                id="progressRemindEveryone" :disabled="$pendingCount === 0">
                                <x-slot:icon><i class="ti ti-send"></i></x-slot:icon>
                            </x-global.btn-detail>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Employees')" :value="$summary['eligible']" icon="users" color="blue"
                    :info="__('Everyone who fills in the survey: every employee of :companies and of the assessed departments, imported every day from Active Directory.', ['companies' => \App\Services\Survey\SurveyReminders::respondentCompanyNames()])"
                    :subtitle="__('The respondents of the survey')" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Not Started')" :value="$summary['not_started']" icon="circle-dashed"
                    color="yellow" :info="__('They have not opened the survey form yet.')" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('In Progress')" :value="$summary['in_progress']" icon="loader" color="purple"
                    :info="__('They opened the survey form or saved a draft, but have not sent it yet.')"
                    :subtitle="__(':count saved as draft', ['count' => $summary['saved_draft']])" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-global.summary-card :title="__('Completed')" :value="$summary['completed']" icon="circle-check" color="green"
                    :info="__('They sent the survey.')"
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
                                <select id="progressDepartmentFilter" class="form-select w-auto" aria-label="{{ __('Department') }}">
                                    <option value="">{{ __('All departments') }}</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
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
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Department') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Company') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Status') }}</th>
                                                    <th class="text-capitalize fs-5 py-3">{{ __('Last Update') }}</th>
                                                    @if ($canSendReminders)
                                                        <th class="text-capitalize fs-5 py-3 text-center">{{ __('Action') }}</th>
                                                    @endif
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
                                {{ __('Only the filling status is shown: answers stay anonymous and are never linked to a person. The list holds every employee of :companies and of the assessed departments, imported every day from Active Directory.', ['companies' => \App\Services\Survey\SurveyReminders::respondentCompanyNames()]) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($canSendReminders)
        {{-- A reminder about the open survey, to everyone who has not completed it or to one person, the ways chosen. --}}
        <div class="modal fade" id="progressReminderModal" tabindex="-1" aria-labelledby="progressReminderTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-3">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    <div class="modal-body p-0">
                        <h3 class="modal-title mb-3" id="progressReminderTitle">{{ __('Send Reminder') }}</h3>
                        <form id="progressReminderForm" novalidate>
                            <div class="mb-3">
                                <div class="form-label">{{ __('Recipients') }}</div>
                                <div class="fw-medium" id="progressReminderWho"></div>
                                <div class="small text-secondary" id="progressReminderWhoMeta"></div>
                            </div>
                            <x-global.reminder-channels :channels="$reminderChannels" :email-sender="$emailSender" id-prefix="progressReminder" />
                            <div class="small text-secondary mb-3">
                                {{ __('Each person gets the reminder in their own language, with a link to the survey. People who completed the survey already are left out.') }}
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm px-3" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <x-global.btn-detail :label="__('Send Reminder')" color="blue" size="sm" type="submit">
                                    <x-slot:icon><i class="ti ti-send"></i></x-slot:icon>
                                </x-global.btn-detail>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('javascript')
    @if ($survey !== null)
        <script>
            const progressSurveyId = @json($survey->id);
            const progressSavedDraft = @json(__('Saved as draft'));
            const progressReminder = {
                url: @json(route('survey-management.reminder.store')),
                pending: @json($pendingCount),
                person: null,
                texts: {
                    everyone: @json(__('Everyone who has not completed it')),
                    people: @json(__(':count people')),
                    remindPerson: @json(__('Send a reminder to this person')),
                    chooseChannel: @json(__('Choose at least one way to remind.')),
                    confirmEveryone: @json(__('This will remind :count people who have not completed the survey.')),
                    confirmPerson: @json(__('This will remind :name.')),
                },
            };

            const progressTable = initTablePage({
                container: '#progressTable',
                url: @json(route('survey-progress.list')),
                defaultSort: 'name',
                columns: [
                    'index',
                    {
                        key: 'name',
                        sortable: true,
                        render: (item) => `<div class="d-flex align-items-center gap-2">
                            ${item.photo_url
                                ? `<span class="avatar rounded-circle flex-shrink-0" style="background-image: url('${escapeHtml(item.photo_url)}')"></span>`
                                : `<span class="avatar rounded-circle flex-shrink-0 bg-blue-lt">${escapeHtml(item.initials)}</span>`}
                            <div><div class="fw-medium">${escapeHtml(item.name)}</div>
                                <div class="small text-secondary">${escapeHtml(item.email)}</div></div>
                        </div>`,
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
                        key: 'department',
                        render: (item) => escapeHtml(item.department || '-'),
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
                    @if ($canSendReminders)
                        {
                            key: 'action',
                            className: 'text-center',
                            render: (item) => item.status === 'completed' ? '' :
                                `<button type="button" class="btn btn-sm btn-icon btn-outline-blue progress-remind" data-id="${escapeHtml(item.id)}"
                                    data-name="${escapeHtml(item.name)}" data-email="${escapeHtml(item.email)}"
                                    title="${escapeHtml(progressReminder.texts.remindPerson)}" aria-label="${escapeHtml(progressReminder.texts.remindPerson)}">
                                    <i class="ti ti-send" aria-hidden="true"></i></button>`,
                        },
                    @endif
                ],
                searchInput: '#progressSearch',
                resetButton: '#progressResetButton',
                filters: () => ({
                    survey_id: progressSurveyId,
                    status: $('#progressStatusFilter').val(),
                    department: $('#progressDepartmentFilter').val() ? [$('#progressDepartmentFilter').val()] : [],
                    company: $('#progressCompanyFilter').val() ? [$('#progressCompanyFilter').val()] : [],
                }),
                onReset: () => {
                    $('#progressStatusFilter').val('');
                    $('#progressDepartmentFilter').val('');
                    $('#progressCompanyFilter').val('');
                },
            });

            $(function() {
                $('#progressStatusFilter, #progressDepartmentFilter, #progressCompanyFilter').on('change', () => progressTable.applyFilters());
                progressTable.reload();

                /** The reminder dialog, for everyone who has not completed the survey (no person) or for one person. */
                function openReminder(person) {
                    progressReminder.person = person;
                    $('#progressReminderWho').text(person ? person.name : progressReminder.texts.everyone);
                    $('#progressReminderWhoMeta').text(person ? person.email : progressReminder.texts.people.replace(':count', progressReminder.pending));
                    $('#progressReminderForm input[name="channels[]"]').prop('checked', true);
                    $('#progressReminderModal').modal('show');
                }

                $('#progressRemindEveryone').on('click', () => openReminder(null));
                $('#progressTable').on('click', '.progress-remind', function() {
                    openReminder({
                        id: $(this).data('id'),
                        name: String($(this).data('name')),
                        email: String($(this).data('email')),
                    });
                });

                $('#progressReminderForm').on('submit', function(event) {
                    event.preventDefault();

                    const person = progressReminder.person;
                    const channels = $(this).find('input[name="channels[]"]:checked').map((index, input) => input.value).get();

                    if (!channels.length) {
                        showError([progressReminder.texts.chooseChannel]);

                        return;
                    }

                    saveRecord({
                        url: progressReminder.url,
                        method: 'POST',
                        data: {
                            survey_id: progressSurveyId,
                            audience: person ? 'custom' : 'all',
                            user_ids: person ? [person.id] : [],
                            channels: channels,
                        },
                        confirmText: person ?
                            progressReminder.texts.confirmPerson.replace(':name', person.name) :
                            progressReminder.texts.confirmEveryone.replace(':count', progressReminder.pending),
                        onSuccess: () => $('#progressReminderModal').modal('hide'),
                    });
                });
            });
        </script>
    @endif
@endpush
