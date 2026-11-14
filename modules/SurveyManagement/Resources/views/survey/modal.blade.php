<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="surveyModal" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content p-3">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <h3 class="modal-title mb-3">Survey</h3>
                <form id="surveyForm" novalidate>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="row">
                                <label for="surveyTitle" class="col-md-3 col-form-label required">Title</label>
                                <div class="col-md-9">
                                    <input type="text" id="surveyTitle" class="form-control" maxlength="255"
                                        placeholder="Operational Audit 2026" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="surveyDescription" class="col-md-3 col-form-label">Description</label>
                                <div class="col-md-9">
                                    <textarea id="surveyDescription" class="form-control" rows="3" maxlength="5000"
                                        placeholder="Shown to employees on the landing page and survey form…"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="surveyStartDate" class="col-md-3 col-form-label required">Period</label>
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <input type="date" id="surveyStartDate" class="form-control" required>
                                        <span class="input-group-text">to</span>
                                        <input type="date" id="surveyEndDate" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="surveyMinutes" class="col-md-3 col-form-label required">Estimated Time</label>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="number" id="surveyMinutes" class="form-control" min="1" max="240"
                                            value="15" required>
                                        <span class="input-group-text">minutes</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="surveyStatus" class="col-md-3 col-form-label required">Status</label>
                                <div class="col-md-4">
                                    <select id="surveyStatus" class="form-select" required>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5 form-hint mt-1">
                                    A survey can be activated once it has questions.
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                            <x-global.btn-detail label="Save" color="blue" size="sm" type="submit"
                                id="surveySubmitButton">
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
        const urlSurveyShow = @json(route('survey-management.survey.show', ':id'));
        const urlSurveyStore = @json(route('survey-management.survey.store'));
        const urlSurveyUpdate = @json(route('survey-management.survey.update', ':id'));
        let surveyModalState = {
            mode: 'add',
            id: null
        };

        function resetSurveyForm() {
            const form = $('#surveyForm');
            form[0].reset();
            form.find('#surveyMinutes').val(15);
            form.find('#surveyStatus').val('draft');
        }

        function toggleSurveyModal(mode, id = null) {
            const modal = $('#surveyModal');

            surveyModalState = {
                mode,
                id
            };
            resetSurveyForm();
            modal.find('.modal-title').text(mode === 'edit' ? 'Edit Survey' : 'Add New Survey');

            if (mode !== 'edit') {
                modal.modal('show');

                return;
            }

            $.getJSON(urlSurveyShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    modal.find('#surveyTitle').val(data.title);
                    modal.find('#surveyDescription').val(data.description);
                    modal.find('#surveyStartDate').val(data.start_date);
                    modal.find('#surveyEndDate').val(data.end_date);
                    modal.find('#surveyMinutes').val(data.estimated_minutes);
                    modal.find('#surveyStatus').val(data.status);
                    modal.modal('show');
                })
                .fail(showAjaxError);
        }

        $('#surveyForm').on('submit', function(event) {
            event.preventDefault();

            const data = {
                title: $('#surveyTitle').val().trim(),
                description: $('#surveyDescription').val().trim(),
                start_date: $('#surveyStartDate').val(),
                end_date: $('#surveyEndDate').val(),
                estimated_minutes: $('#surveyMinutes').val(),
                status: $('#surveyStatus').val(),
            };

            const errors = [];

            if (!data.title) {
                errors.push('Title cannot be empty.');
            }

            if (!data.start_date || !data.end_date) {
                errors.push('Period start and end dates are required.');
            } else if (data.end_date < data.start_date) {
                errors.push('The end date must be on or after the start date.');
            }

            if (errors.length) {
                showError(errors);

                return;
            }

            const isEdit = surveyModalState.mode === 'edit';

            saveRecord({
                url: isEdit ? urlSurveyUpdate.replace(':id', surveyModalState.id) : urlSurveyStore,
                method: isEdit ? 'PUT' : 'POST',
                data,
                confirmText: `This action will ${isEdit ? 'update' : 'create'} the survey!`,
                onSuccess: () => {
                    $('#surveyModal').modal('hide');
                    surveyTable.reload();
                },
            });
        });
    </script>
@endpush
