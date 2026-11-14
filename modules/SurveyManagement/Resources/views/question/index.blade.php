@extends('main.index')

@section('page-title', 'Questions')

@section('content')
    <div class="row g-2">
        {{-- Survey selector & filter --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="surveyFilter" class="form-label">Survey Period</label>
                            <select id="surveyFilter" class="form-select">
                                <option value="">All surveys</option>
                                @foreach ($surveys as $survey)
                                    <option value="{{ $survey->id }}" data-locked="{{ $survey->responses_count > 0 ? 1 : 0 }}"
                                        @selected($selectedSurveyId === $survey->id)>
                                        {{ $survey->title }} ({{ $survey->status->label() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="departmentFilter" class="form-label">Department</label>
                            <select id="departmentFilter" class="form-select">
                                <option value="">All departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                                <option value="general">General</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sectionFilter" class="form-label">Section</label>
                            <select id="sectionFilter" class="form-select" disabled>
                                <option value="">All sections</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}" data-department="{{ $section->department_id }}">
                                        {{ $section->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="typeFilter" class="form-label">Type</label>
                            <select id="typeFilter" class="form-select">
                                <option value="">All types</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-none" id="lockedNotice">
            <div class="alert alert-warning mb-0" role="alert">
                <div class="d-flex gap-2">
                    <i class="ti ti-lock fs-2"></i>
                    <div>This survey already has responses. Its questions are locked so earlier answers keep their
                        meaning. Duplicate the survey to prepare a new question set.</div>
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
                                <input type="text" id="questionSearch" class="form-control rounded-3" autocomplete="off"
                                    placeholder="Search…" />
                                <x-global.btn-detail label="" color="blue" size="sm" outline="true" id="toggleSearchButton"
                                    data-bs-toggle="tooltip" data-bs-title="Search">
                                    <x-slot:icon><i class="ti ti-search"></i></x-slot:icon>
                                </x-global.btn-detail>
                                <x-global.btn-detail label="" color="red" size="sm" outline="true" id="toggleResetButton"
                                    data-bs-toggle="tooltip" data-bs-title="Reset">
                                    <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                                </x-global.btn-detail>
                            </div>
                            @if ($can['create'] && $surveys->isNotEmpty())
                                <x-global.btn-detail label="Add Question" color="blue" size="sm" id="addQuestionButton"
                                    onclick="toggleQuestionModal('add')">
                                    <x-slot:icon><i class="ti ti-plus"></i></x-slot:icon>
                                </x-global.btn-detail>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100" id="questionTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">No</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="sort_order" width="70">Order</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="question">Question</th>
                                                <th class="text-capitalize fs-5 py-3">Department / Section</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="type">Type</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="is_required">Required</th>
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

    @include('SurveyManagement::question.modal')
@endsection

@push('javascript')
    <script>
        const urlQuestionList = @json(route('survey-management.question.list'));
        const urlQuestionDestroy = @json(route('survey-management.question.destroy', ':id'));
        const questionCan = @json($can);

        const questionColumns = [
            'index',
            {
                key: 'sort_order',
                sortable: true,
                className: 'text-center',
            },
            {
                key: 'question',
                sortable: true,
                render: (item) => `<div style="min-width: 280px; white-space: normal">${escapeHtml(item.question)}</div>
                    ${item.help_text ? `<div class="text-secondary small" style="white-space: normal">${escapeHtml(item.help_text)}</div>` : ''}
                    ${$('#surveyFilter').val() ? '' : `<div class="text-secondary small"><i class="ti ti-clipboard-list me-1"></i>${escapeHtml(item.survey)}</div>`}`,
            },
            {
                key: 'department',
                className: 'text-nowrap',
                render: (item) => `<span class="badge mdka-bg-${escapeHtml(item.department_color)}-100 mdka-text-${escapeHtml(item.department_color)}-600">${escapeHtml(item.department)}</span>
                    ${item.section ? `<div class="text-secondary small mt-1"><i class="ti ti-corner-down-right me-1"></i>${escapeHtml(item.section)}</div>` : ''}`,
            },
            {
                key: 'type',
                sortable: true,
                className: 'text-center text-nowrap',
                render: (item) => `<span class="badge ${({ rating: 'bg-blue-lt', criticality: 'bg-orange-lt' })[item.type] || 'bg-azure-lt'}">${escapeHtml(item.type_label)}</span>`,
            },
            {
                key: 'is_required',
                sortable: true,
                className: 'text-center',
                render: (item) => item.is_required ? '<i class="ti ti-circle-check text-green fs-2"></i>' : '<i class="ti ti-circle-dashed text-muted fs-2"></i>',
            },
            {
                key: 'actions',
                className: 'text-center text-nowrap',
                render: (item) => {
                    if (item.is_locked) {
                        return '<span class="text-muted" data-bs-toggle="tooltip" data-bs-title="Survey has responses"><i class="ti ti-lock"></i></span>';
                    }

                    let html = '<div class="d-inline-flex gap-1">';

                    if (questionCan.update) {
                        html += `<x-global.btn-detail label="" color="orange" size="sm" onclick="toggleQuestionModal('edit', ${item.id})" data-bs-toggle="tooltip" data-bs-title="Edit">
                            <x-slot:icon><i class="ti ti-edit"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    if (questionCan.delete) {
                        html += `<x-global.btn-detail label="" color="red" size="sm" onclick="deleteQuestion(${item.id})" data-bs-toggle="tooltip" data-bs-title="Delete">
                            <x-slot:icon><i class="ti ti-trash"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    return html + '</div>';
                },
            },
        ];

        const questionTable = initTablePage({
            container: '#questionTable',
            url: urlQuestionList,
            columns: questionColumns,
            defaultSort: 'sort_order',
            searchInput: '#questionSearch',
            searchButton: '#toggleSearchButton',
            resetButton: '#toggleResetButton',
            filters: () => ({
                survey_id: $('#surveyFilter').val() || null,
                department_id: $('#departmentFilter').val() || null,
                department_section_id: $('#sectionFilter').val() || null,
                type: $('#typeFilter').val() || null,
            }),
            onReset: () => {
                $('#departmentFilter').val('');
                $('#typeFilter').val('');
                syncSectionFilter();
            },
        });

        function updateLockedNotice() {
            const locked = $('#surveyFilter option:selected').data('locked') == 1;

            $('#lockedNotice').toggleClass('d-none', !locked);
            $('#addQuestionButton').prop('disabled', locked);
        }

        /**
         * Only offer the sections of the selected department.
         */
        function syncSectionFilter() {
            const departmentId = $('#departmentFilter').val();
            const select = $('#sectionFilter');
            const hasDepartment = departmentId && departmentId !== 'general';

            select.val('').prop('disabled', !hasDepartment);
            select.find('option[data-department]').each(function() {
                $(this).prop('hidden', String($(this).data('department')) !== departmentId);
            });
        }

        $(function() {
            updateLockedNotice();
            syncSectionFilter();
            questionTable.reload();

            $('#departmentFilter').on('change', syncSectionFilter);
            $('#surveyFilter, #departmentFilter, #sectionFilter, #typeFilter').on('change', () => {
                updateLockedNotice();
                questionTable.applyFilters();
            });
        });

        function deleteQuestion(id) {
            deleteRecord({
                url: urlQuestionDestroy.replace(':id', id),
                onSuccess: () => questionTable.reload(),
            });
        }
    </script>
@endpush
