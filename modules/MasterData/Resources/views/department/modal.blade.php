<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="departmentModal" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content p-3">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            <div class="modal-body p-0">
                <h3 class="modal-title mb-3">{{ __('Department') }}</h3>
                <form id="departmentForm" novalidate>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="row">
                                <label for="departmentCode" class="col-md-3 col-form-label required">{{ __('Code') }}</label>
                                <div class="col-md-4">
                                    <input type="text" id="departmentCode" class="form-control text-uppercase"
                                        maxlength="20" placeholder="HR" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="departmentName" class="col-md-3 col-form-label required">{{ __('Name') }}</label>
                                <div class="col-md-9">
                                    <input type="text" id="departmentName" class="form-control" maxlength="255"
                                        placeholder="Supply Chain" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="departmentDescription" class="col-md-3 col-form-label">{{ __('Description') }}</label>
                                <div class="col-md-9">
                                    <textarea id="departmentDescription" class="form-control" rows="2" maxlength="2000"
                                        placeholder="{{ __('Shown at the top of the department step of the survey') }}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="departmentImage" class="col-md-3 col-form-label">{{ __('Image') }}</label>
                                <div class="col-md-9">
                                    <div class="d-flex flex-column flex-sm-row gap-3 align-items-sm-center">
                                        <img id="departmentImagePreview" src="" alt="{{ __('Department image preview') }}"
                                            class="rounded border d-none" style="width: 160px; height: 90px; object-fit: cover">
                                        <div class="flex-fill">
                                            <input type="file" id="departmentImage" class="form-control"
                                                accept="image/jpeg,image/png,image/webp">
                                            <small class="form-hint">{{ __('JPG, PNG or WebP, max 2 MB. Shown on the landing page and the survey step.') }}</small>
                                            <label class="form-check mt-1 mb-0 d-none" id="departmentRemoveImageWrapper">
                                                <input class="form-check-input" type="checkbox" id="departmentRemoveImage">
                                                <span class="form-check-label">{{ __('Remove current image') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="departmentIcon" class="col-md-3 col-form-label">{{ __('Icon') }}</label>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ti ti-building" id="departmentIconPreview"></i></span>
                                        <input type="text" id="departmentIcon" class="form-control" maxlength="100"
                                            placeholder="users">
                                    </div>
                                    <small class="form-hint">{{ __('Tabler icon name, used when there is no image.') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label class="col-md-3 col-form-label required">{{ __('Color') }}</label>
                                <div class="col-md-9">
                                    <div class="row g-2">
                                        @foreach ($colors as $color)
                                            <div class="col-auto">
                                                <label class="form-colorinput" title="{{ __(ucfirst($color)) }}">
                                                    <input name="departmentColor" type="radio" value="{{ $color }}"
                                                        class="form-colorinput-input">
                                                    <span class="form-colorinput-color mdka-bg-{{ $color }}-500"></span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="departmentOrder" class="col-md-3 col-form-label required">{{ __('Display Order') }}</label>
                                <div class="col-md-3">
                                    <input type="number" id="departmentOrder" class="form-control" min="0" max="65535"
                                        value="0" required>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="departmentActive" checked>
                                        <span class="form-check-label">{{ __('Active (assessed in surveys)') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="departmentManagerJobTitles" class="col-md-3 col-form-label">{{ __('Manager Job Titles') }}</label>
                                <div class="col-md-9">
                                    <textarea id="departmentManagerJobTitles" class="form-control font-monospace" rows="2"
                                        maxlength="1000" placeholder="OHS Manager"></textarea>
                                    <small class="form-hint">{!! __('One job title per line (:wildcard any text, :exclusion excludes).', ['wildcard' => '<code>*</code>', 'exclusion' => '<code>!</code>']) !!}
                                        {{ __("Managers with these job titles see this department's survey results.") }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-3 d-flex justify-content-between align-items-center gap-2">
                            <span class="text-secondary small"><i class="ti ti-info-circle me-1"></i>{{ __('Sections are managed in Master Data › Sections.') }}</span>
                            <x-global.btn-detail :label="__('Save')" color="blue" size="sm" type="submit">
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
        const urlDepartmentShow = @json(route('master-data.department.show', ':id'));
        const urlDepartmentStore = @json(route('master-data.department.store'));
        const urlDepartmentUpdate = @json(route('master-data.department.update', ':id'));
        let departmentModalState = {
            mode: 'add',
            id: null
        };

        function updateIconPreview() {
            const icon = ($('#departmentIcon').val() || 'building').replace(/[^a-z0-9-]/g, '');
            $('#departmentIconPreview').attr('class', `ti ti-${icon || 'building'}`);
        }

        function toggleDepartmentModal(mode, id = null) {
            const modal = $('#departmentModal');
            const form = $('#departmentForm');

            departmentModalState = {
                mode,
                id
            };
            form[0].reset();
            form.find('input[name="departmentColor"][value="blue"]').prop('checked', true);
            form.find('#departmentImagePreview').attr('src', '').addClass('d-none');
            form.find('#departmentRemoveImageWrapper').addClass('d-none');
            modal.find('.modal-title').text(mode === 'edit' ? @json(__('Edit Department')) : @json(__('Add New Department')));
            updateIconPreview();

            if (mode !== 'edit') {
                modal.modal('show');

                return;
            }

            $.getJSON(urlDepartmentShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    form.find('#departmentCode').val(data.code);
                    form.find('#departmentName').val(data.name);
                    form.find('#departmentDescription').val(data.description);
                    form.find('#departmentIcon').val(data.icon);
                    form.find(`input[name="departmentColor"][value="${CSS.escape(data.color)}"]`).prop('checked', true);
                    form.find('#departmentOrder').val(data.sort_order);
                    form.find('#departmentActive').prop('checked', !!data.is_active);
                    form.find('#departmentManagerJobTitles').val(data.manager_job_titles || '');

                    if (data.image_url) {
                        form.find('#departmentImagePreview').attr('src', data.image_url).removeClass('d-none');
                        form.find('#departmentRemoveImageWrapper').removeClass('d-none');
                    }

                    updateIconPreview();
                    modal.modal('show');
                })
                .fail(showAjaxError);
        }

        $('#departmentIcon').on('input', updateIconPreview);
        $('#departmentImage').on('change', function() {
            previewImage(this, '#departmentImagePreview');
            $('#departmentRemoveImage').prop('checked', false);
        });

        $('#departmentForm').on('submit', function(event) {
            event.preventDefault();

            const data = new FormData();
            data.append('code', $('#departmentCode').val().trim());
            data.append('name', $('#departmentName').val().trim());
            data.append('description', $('#departmentDescription').val().trim());
            data.append('icon', $('#departmentIcon').val().trim());
            data.append('color', $('input[name="departmentColor"]:checked').val() || '');
            data.append('sort_order', $('#departmentOrder').val());
            data.append('is_active', $('#departmentActive').is(':checked') ? 1 : 0);
            data.append('manager_job_titles', $('#departmentManagerJobTitles').val());
            data.append('remove_image', $('#departmentRemoveImage').is(':checked') ? 1 : 0);

            const image = $('#departmentImage')[0].files[0];

            if (image) {
                data.append('image', image);
            }

            const errors = [];

            if (!data.get('code')) {
                errors.push(@json(__('Code cannot be empty.')));
            }

            if (!data.get('name')) {
                errors.push(@json(__('Name cannot be empty.')));
            }

            if (image && image.size > 2 * 1024 * 1024) {
                errors.push(@json(__('The image may not be larger than 2 MB.')));
            }

            if (errors.length) {
                showError(errors);

                return;
            }

            const isEdit = departmentModalState.mode === 'edit';

            saveRecord({
                url: isEdit ? urlDepartmentUpdate.replace(':id', departmentModalState.id) : urlDepartmentStore,
                method: isEdit ? 'PUT' : 'POST',
                data,
                confirmText: isEdit ? @json(__('This action will update the department!')) : @json(__('This action will create the department!')),
                onSuccess: () => {
                    $('#departmentModal').modal('hide');
                    departmentTable.reload();
                },
            });
        });
    </script>
@endpush
