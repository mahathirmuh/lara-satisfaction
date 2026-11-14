<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="questionModal" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content p-3">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <h3 class="modal-title mb-3">Question</h3>
                <form id="questionForm" novalidate>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="row">
                                <label for="questionSurvey" class="col-md-3 col-form-label required">Survey Period</label>
                                <div class="col-md-9">
                                    <select id="questionSurvey" class="form-select" required>
                                        @foreach ($surveys as $survey)
                                            <option value="{{ $survey->id }}" @disabled($survey->responses_count > 0)>
                                                {{ $survey->title }}{{ $survey->responses_count > 0 ? ' (locked)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="questionDepartment" class="col-md-3 col-form-label">Department</label>
                                <div class="col-md-9">
                                    <select id="questionDepartment" class="form-select">
                                        <option value="">General (not tied to a department)</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">
                                                {{ $department->name }}{{ $department->is_active ? '' : ' (inactive)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12" id="questionSectionRow">
                            <div class="row">
                                <label for="questionSection" class="col-md-3 col-form-label">Section</label>
                                <div class="col-md-9">
                                    <select id="questionSection" class="form-select">
                                        <option value="">Any section (asked about the section the employee chooses)</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}" data-department="{{ $section->department_id }}">
                                                Only {{ $section->name }}{{ $section->is_active ? '' : ' (inactive)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-hint">Employees choose one section per department to evaluate. Pick a
                                        section only for a question that is specific to it.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label class="col-md-3 col-form-label required">Type</label>
                                <div class="col-md-9">
                                    <div class="form-selectgroup">
                                        @foreach ($types as $value => $label)
                                            <label class="form-selectgroup-item">
                                                <input type="radio" name="questionType" value="{{ $value }}"
                                                    class="form-selectgroup-input" @checked($loop->first)>
                                                <span class="form-selectgroup-label">
                                                    <i class="ti {{ match ($value) { 'rating' => 'ti-star', 'criticality' => 'ti-alert-triangle', default => 'ti-message-2' } }} me-1"></i>{{ $label }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="questionText" class="col-md-3 col-form-label required">Question</label>
                                <div class="col-md-9">
                                    <textarea id="questionText" class="form-control" rows="3" maxlength="1000"
                                        placeholder="How would you rate the service quality of {section} towards your department?"
                                        required></textarea>
                                    <small class="form-hint">Write <code>{section}</code> where the name of the evaluated
                                        section should appear.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="questionHelp" class="col-md-3 col-form-label">Help Text</label>
                                <div class="col-md-9">
                                    <input type="text" id="questionHelp" class="form-control" maxlength="500"
                                        placeholder="Optional hint shown below the question">
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="questionOrder" class="col-md-3 col-form-label">Display Order</label>
                                <div class="col-md-3">
                                    <input type="number" id="questionOrder" class="form-control" min="0" max="65535"
                                        placeholder="Auto">
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="questionRequired" checked>
                                        <span class="form-check-label">Answer required</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                            <x-global.btn-detail label="Save" color="blue" size="sm" type="submit">
                                <x-slot:icon><i class="ti ti-device-floppy"></i></x-slot:icon>
                            </x-global.btn-detail>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('javascript')
    <script>
        const urlQuestionShow = @json(route('survey-management.question.show', ':id'));
        const urlQuestionStore = @json(route('survey-management.question.store'));
        const urlQuestionUpdate = @json(route('survey-management.question.update', ':id'));
        let questionModalState = {
            mode: 'add',
            id: null
        };

        /**
         * Show only the sections of the selected department (none for general questions).
         */
        function syncQuestionSections(selectedSectionId = '') {
            const departmentId = $('#questionDepartment').val();
            const select = $('#questionSection');

            $('#questionSectionRow').toggleClass('d-none', !departmentId);
            select.find('option[data-department]').each(function() {
                $(this).prop('hidden', String($(this).data('department')) !== departmentId);
            });
            select.val(selectedSectionId && select.find(`option[value="${parseInt(selectedSectionId, 10)}"]:not([hidden])`).length ? String(selectedSectionId) : '');
        }

        $('#questionDepartment').on('change', () => syncQuestionSections());

        function toggleQuestionModal(mode, id = null) {
            const modal = $('#questionModal');
            const form = $('#questionForm');

            questionModalState = {
                mode,
                id
            };
            form[0].reset();
            modal.find('.modal-title').text(mode === 'edit' ? 'Edit Question' : 'Add New Question');

            if (mode !== 'edit') {
                const selectedSurvey = $('#surveyFilter').val();
                const selectedDepartment = $('#departmentFilter').val();

                if (selectedSurvey) {
                    form.find('#questionSurvey').val(selectedSurvey);
                }

                if (selectedDepartment && selectedDepartment !== 'general') {
                    form.find('#questionDepartment').val(selectedDepartment);
                }

                syncQuestionSections($('#sectionFilter').val());
                modal.modal('show');

                return;
            }

            $.getJSON(urlQuestionShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    form.find('#questionSurvey').val(data.survey_id);
                    form.find('#questionDepartment').val(data.department_id ?? '');
                    syncQuestionSections(data.department_section_id ?? '');
                    form.find(`input[name="questionType"][value="${CSS.escape(data.type)}"]`).prop('checked', true);
                    form.find('#questionText').val(data.question);
                    form.find('#questionHelp').val(data.help_text);
                    form.find('#questionOrder').val(data.sort_order);
                    form.find('#questionRequired').prop('checked', !!data.is_required);
                    modal.modal('show');
                })
                .fail(showAjaxError);
        }

        $('#questionForm').on('submit', function(event) {
            event.preventDefault();

            const data = {
                survey_id: $('#questionSurvey').val(),
                department_id: $('#questionDepartment').val() || null,
                department_section_id: $('#questionDepartment').val() ? ($('#questionSection').val() || null) : null,
                type: $('input[name="questionType"]:checked').val(),
                question: $('#questionText').val().trim(),
                help_text: $('#questionHelp').val().trim(),
                sort_order: $('#questionOrder').val() || null,
                is_required: $('#questionRequired').is(':checked') ? 1 : 0,
            };

            if (!data.survey_id || !data.question) {
                showError(['Survey period and question text are required.']);

                return;
            }

            const isEdit = questionModalState.mode === 'edit';

            saveRecord({
                url: isEdit ? urlQuestionUpdate.replace(':id', questionModalState.id) : urlQuestionStore,
                method: isEdit ? 'PUT' : 'POST',
                data,
                confirmText: `This action will ${isEdit ? 'update' : 'create'} the question!`,
                onSuccess: () => {
                    $('#questionModal').modal('hide');
                    questionTable.reload();
                },
            });
        });
    </script>
@endpush
