<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="sectionModal" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content p-3">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <h3 class="modal-title mb-3">Section</h3>
                <form id="sectionForm" novalidate>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label for="sectionDepartment" class="form-label required">Department</label>
                            <select id="sectionDepartment" class="form-select" required>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->name }}{{ $department->is_active ? '' : ' (inactive)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label for="sectionName" class="form-label required">Section</label>
                            <input type="text" id="sectionName" class="form-control" maxlength="255"
                                placeholder="Asset Protection" required>
                        </div>
                        <div class="col-12">
                            <label for="sectionDescription" class="form-label">Role Overview</label>
                            <textarea id="sectionDescription" class="form-control" rows="4" maxlength="3000"
                                placeholder="What this section does for the other departments. Employees read it before they evaluate the section."></textarea>
                        </div>

                        @foreach ([
                            'team_photo' => ['label' => 'Team Picture', 'caption' => 'team_caption', 'placeholder' => 'Asset Protection Team (Left to Right): …'],
                            'work_summary' => ['label' => 'Work Summary', 'caption' => 'work_summary_caption', 'placeholder' => 'Work Summary Matrix Asset Protection Semester 1 - 2026'],
                        ] as $field => $image)
                            <div class="col-lg-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="form-label">{{ $image['label'] }}</div>
                                    <img src="" alt="{{ $image['label'] }} preview" data-preview="{{ $field }}"
                                        class="section-image-preview rounded border mb-2 d-none">
                                    <input type="file" class="form-control" data-image="{{ $field }}"
                                        accept="image/jpeg,image/png,image/webp" aria-label="{{ $image['label'] }} file">
                                    <small class="form-hint">JPG, PNG or WebP, max 2 MB.</small>
                                    <label class="form-check mt-1 mb-2 d-none" data-remove-wrapper="{{ $field }}">
                                        <input class="form-check-input" type="checkbox" data-remove="{{ $field }}">
                                        <span class="form-check-label">Remove current {{ strtolower($image['label']) }}</span>
                                    </label>
                                    <input type="text" class="form-control mt-2" data-caption="{{ $image['caption'] }}"
                                        maxlength="255" placeholder="{{ $image['placeholder'] }}"
                                        aria-label="{{ $image['label'] }} caption">
                                </div>
                            </div>
                        @endforeach

                        <div class="col-lg-6">
                            <label for="sectionOrder" class="form-label">Display Order</label>
                            <input type="number" id="sectionOrder" class="form-control" min="0" max="65535"
                                placeholder="Auto">
                        </div>
                        <div class="col-lg-6 d-flex align-items-end">
                            <label class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="sectionActive" checked>
                                <span class="form-check-label">Active (employees can choose it)</span>
                            </label>
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

@push('style')
    <style>
        .section-image-preview {
            display: block;
            width: 100%;
            max-height: 180px;
            object-fit: cover;
        }
    </style>
@endpush

@push('javascript')
    <script>
        const urlSectionShow = @json(route('master-data.section.show', ':id'));
        const urlSectionStore = @json(route('master-data.section.store'));
        const urlSectionUpdate = @json(route('master-data.section.update', ':id'));
        const sectionImageFields = ['team_photo', 'work_summary'];
        let sectionModalState = {
            mode: 'add',
            id: null
        };

        function toggleSectionModal(mode, id = null) {
            const modal = $('#sectionModal');
            const form = $('#sectionForm');

            sectionModalState = {
                mode,
                id
            };
            form[0].reset();
            form.find('[data-preview]').attr('src', '').addClass('d-none');
            form.find('[data-remove-wrapper]').addClass('d-none');
            modal.find('.modal-title').text(mode === 'edit' ? 'Edit Section' : 'Add New Section');

            if (mode !== 'edit') {
                const selectedDepartment = $('#departmentFilter').val();

                if (selectedDepartment) {
                    form.find('#sectionDepartment').val(selectedDepartment);
                }

                modal.modal('show');

                return;
            }

            $.getJSON(urlSectionShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    form.find('#sectionDepartment').val(data.department_id);
                    form.find('#sectionName').val(data.name);
                    form.find('#sectionDescription').val(data.description);
                    form.find('[data-caption="team_caption"]').val(data.team_caption);
                    form.find('[data-caption="work_summary_caption"]').val(data.work_summary_caption);
                    form.find('#sectionOrder').val(data.sort_order);
                    form.find('#sectionActive').prop('checked', !!data.is_active);

                    sectionImageFields.forEach((field) => {
                        const url = data[`${field}_url`];

                        if (url) {
                            form.find(`[data-preview="${field}"]`).attr('src', url).removeClass('d-none');
                            form.find(`[data-remove-wrapper="${field}"]`).removeClass('d-none');
                        }
                    });

                    modal.modal('show');
                })
                .fail(showAjaxError);
        }

        $('#sectionForm [data-image]').on('change', function() {
            const field = $(this).data('image');

            previewImage(this, `#sectionForm [data-preview="${field}"]`);
            $(`#sectionForm [data-remove="${field}"]`).prop('checked', false);
        });

        $('#sectionForm').on('submit', function(event) {
            event.preventDefault();

            const form = $(this);
            const data = new FormData();
            const errors = [];

            data.append('department_id', $('#sectionDepartment').val() || '');
            data.append('name', $('#sectionName').val().trim());
            data.append('description', $('#sectionDescription').val().trim());
            data.append('team_caption', form.find('[data-caption="team_caption"]').val().trim());
            data.append('work_summary_caption', form.find('[data-caption="work_summary_caption"]').val().trim());
            data.append('sort_order', $('#sectionOrder').val() || '');
            data.append('is_active', $('#sectionActive').is(':checked') ? 1 : 0);

            sectionImageFields.forEach((field) => {
                const file = form.find(`[data-image="${field}"]`)[0].files[0];

                data.append(`remove_${field}`, form.find(`[data-remove="${field}"]`).is(':checked') ? 1 : 0);

                if (file) {
                    data.append(field, file);

                    if (file.size > 2 * 1024 * 1024) {
                        errors.push(`The ${field.replace('_', ' ')} may not be larger than 2 MB.`);
                    }
                }
            });

            if (!data.get('department_id') || !data.get('name')) {
                errors.unshift('Department and section name are required.');
            }

            if (errors.length) {
                showError(errors);

                return;
            }

            const isEdit = sectionModalState.mode === 'edit';

            saveRecord({
                url: isEdit ? urlSectionUpdate.replace(':id', sectionModalState.id) : urlSectionStore,
                method: isEdit ? 'PUT' : 'POST',
                data,
                confirmText: `This action will ${isEdit ? 'update' : 'create'} the section!`,
                onSuccess: () => {
                    $('#sectionModal').modal('hide');
                    sectionTable.reload();
                },
            });
        });
    </script>
@endpush
