@extends('main.index')

@section('page-title', 'Survey Period')

@section('content')
    <div class="row g-2">
        {{-- Filter --}}
        <div class="col-12">
            <div class="card" id="surveyFilterCard" style="display: none;">
                <div class="card-header p-2">
                    <h4 class="mb-0">Filter</h4>
                </div>
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All status</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <x-global.btn-detail label="" color="green" size="sm" outline="true" id="applyFilterButton"
                                data-bs-toggle="tooltip" data-bs-title="Apply Filter">
                                <x-slot:icon><i class="ti ti-check"></i></x-slot:icon>
                            </x-global.btn-detail>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-12 d-flex flex-wrap justify-content-between gap-2">
                            <div class="d-flex gap-2">
                                <input type="text" id="surveySearch" class="form-control rounded-3" autocomplete="off"
                                    placeholder="Search…" />
                                <x-global.btn-detail label="" color="blue" size="sm" outline="true" id="toggleSearchButton"
                                    data-bs-toggle="tooltip" data-bs-title="Search">
                                    <x-slot:icon><i class="ti ti-search"></i></x-slot:icon>
                                </x-global.btn-detail>
                                <x-global.btn-detail label="" color="purple" size="sm" outline="true" id="toggleFilterButton"
                                    data-bs-toggle="tooltip" data-bs-title="Filter">
                                    <x-slot:icon><i class="ti ti-filter"></i></x-slot:icon>
                                </x-global.btn-detail>
                                <x-global.btn-detail label="" color="red" size="sm" outline="true" id="toggleResetButton"
                                    data-bs-toggle="tooltip" data-bs-title="Reset">
                                    <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                                </x-global.btn-detail>
                            </div>
                            @if ($can['create'])
                                <x-global.btn-detail label="Add Survey" color="blue" size="sm"
                                    onclick="toggleSurveyModal('add')">
                                    <x-slot:icon><i class="ti ti-plus"></i></x-slot:icon>
                                </x-global.btn-detail>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap" id="surveyTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">No</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="title">Title</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="start_date">Period</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="status">Status</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">Questions</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">Responses</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">Action</th>
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
    </div>

    @include('SurveyManagement::survey.modal')
@endsection

@push('javascript')
    <script>
        const urlSurveyList = @json(route('survey-management.survey.list'));
        const urlSurveyStatus = @json(route('survey-management.survey.status', ':id'));
        const urlSurveyDuplicate = @json(route('survey-management.survey.duplicate', ':id'));
        const urlSurveyDestroy = @json(route('survey-management.survey.destroy', ':id'));
        const urlQuestionIndex = @json(route('survey-management.question.index'));
        const urlReportShow = @json(route('survey-report.show', ':id'));
        const surveyCan = @json($can);
        const canManageQuestions = @json($canManageQuestions);
        const canViewReports = @json($canViewReports);

        const statusActions = {
            draft: [{
                status: 'active',
                label: 'Publish',
                icon: 'player-play',
                color: 'green'
            }],
            active: [{
                status: 'closed',
                label: 'Close',
                icon: 'lock',
                color: 'red'
            }, {
                status: 'draft',
                label: 'Back to Draft',
                icon: 'pencil',
                color: 'gray'
            }],
            closed: [{
                status: 'active',
                label: 'Re-open',
                icon: 'lock-open',
                color: 'green'
            }],
        };

        const surveyColumns = [
            'index',
            {
                key: 'title',
                sortable: true,
                render: (item) => `<div class="fw-medium">${escapeHtml(item.title)}</div>
                    <div class="text-secondary small"><i class="ti ti-clock me-1"></i>${escapeHtml(item.estimated_minutes)} min</div>`,
            },
            {
                key: 'start_date',
                sortable: true,
                render: (item) => escapeHtml(item.period),
            },
            {
                key: 'status',
                sortable: true,
                className: 'text-center',
                render: (item) => `<span class="status status-${escapeHtml(item.status_color)}"><span class="status-dot ${item.is_open ? 'status-dot-animated' : ''}"></span>${escapeHtml(item.status_label)}</span>`,
            },
            {
                key: 'questions_count',
                className: 'text-center',
                render: (item) => canManageQuestions ?
                    `<a href="${urlQuestionIndex}?survey_id=${item.id}">${escapeHtml(item.questions_count)}</a>` : escapeHtml(item.questions_count),
            },
            {
                key: 'responses_count',
                className: 'text-center',
            },
            {
                key: 'actions',
                className: 'text-center',
                render: (item) => {
                    let html = '<div class="d-inline-flex gap-1">';

                    if (surveyCan.update) {
                        html += `<x-global.btn-detail label="" color="orange" size="sm" onclick="toggleSurveyModal('edit', ${item.id})" data-bs-toggle="tooltip" data-bs-title="Edit">
                            <x-slot:icon><i class="ti ti-edit"></i></x-slot:icon>
                        </x-global.btn-detail>`;

                        (statusActions[item.status] || []).forEach((action) => {
                            html += `<button type="button" class="btn btn-sm px-2 py-1 rounded-2 btn-outline-${action.color}" onclick="changeSurveyStatus(${item.id}, '${action.status}', '${action.label}')" data-bs-toggle="tooltip" data-bs-title="${action.label}"><i class="ti ti-${action.icon}"></i></button>`;
                        });
                    }

                    if (surveyCan.create) {
                        html += `<x-global.btn-detail label="" color="purple" size="sm" outline="true" onclick="duplicateSurvey(${item.id})" data-bs-toggle="tooltip" data-bs-title="Duplicate">
                            <x-slot:icon><i class="ti ti-copy"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    if (canViewReports && item.status !== 'draft') {
                        html += `<a class="btn btn-sm px-2 py-1 rounded-2 btn-outline-blue" href="${urlReportShow.replace(':id', item.id)}" data-bs-toggle="tooltip" data-bs-title="Report"><i class="ti ti-report-analytics"></i></a>`;
                    }

                    if (surveyCan.delete && item.responses_count === 0) {
                        html += `<x-global.btn-detail label="" color="red" size="sm" onclick="deleteSurvey(${item.id})" data-bs-toggle="tooltip" data-bs-title="Delete">
                            <x-slot:icon><i class="ti ti-trash"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    return html + '</div>';
                },
            },
        ];

        const surveyTable = initTablePage({
            container: '#surveyTable',
            url: urlSurveyList,
            columns: surveyColumns,
            defaultSort: 'start_date',
            defaultDir: 'desc',
            searchInput: '#surveySearch',
            searchButton: '#toggleSearchButton',
            resetButton: '#toggleResetButton',
            filters: () => ({
                status: $('#statusFilter').val() || null,
            }),
            onReset: () => {
                $('#statusFilter').val('');
                $('#surveyFilterCard').slideUp(300);
            },
        });

        $(function() {
            surveyTable.reload();

            $('#toggleFilterButton').on('click', () => $('#surveyFilterCard').slideToggle(300));
            $('#applyFilterButton').on('click', () => surveyTable.applyFilters());
        });

        function changeSurveyStatus(id, status, label) {
            saveRecord({
                url: urlSurveyStatus.replace(':id', id),
                method: 'PATCH',
                data: {
                    status
                },
                confirmText: `The survey will be changed to "${label}".`,
                onSuccess: () => surveyTable.reload(),
            });
        }

        function duplicateSurvey(id) {
            saveRecord({
                url: urlSurveyDuplicate.replace(':id', id),
                method: 'POST',
                confirmText: 'A draft copy of this survey (including its questions) will be created.',
                onSuccess: () => surveyTable.reload(),
            });
        }

        function deleteSurvey(id) {
            deleteRecord({
                url: urlSurveyDestroy.replace(':id', id),
                onSuccess: () => surveyTable.reload(),
            });
        }
    </script>
@endpush
