<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="userModal" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content p-3">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <h3 class="modal-title mb-3">User</h3>
                <form id="userForm" novalidate autocomplete="off">
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="row">
                                <label for="userName" class="col-md-3 col-form-label required">Name</label>
                                <div class="col-md-9">
                                    <input type="text" id="userName" class="form-control" maxlength="255"
                                        placeholder="Name.." required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="userEmail" class="col-md-3 col-form-label required">Email</label>
                                <div class="col-md-9">
                                    <input type="email" id="userEmail" class="form-control" maxlength="255"
                                        placeholder="name@merdekabattery.com" required>
                                    <small class="form-hint">The email of their Microsoft / network account: it is
                                        how the user signs in (there are no passwords in this application).</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="userEmployeeId" class="col-md-3 col-form-label">Employee ID</label>
                                <div class="col-md-5">
                                    <input type="text" id="userEmployeeId" class="form-control" maxlength="50"
                                        placeholder="e.g. MTI240001">
                                </div>
                                <div class="col-md-9 offset-md-3">
                                    <small class="form-hint">Filled from the directory (Active Directory / Microsoft) at
                                        every sign-in; enter it here only for someone who has not signed in yet.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="userJobTitle" class="col-md-3 col-form-label">Job Title</label>
                                <div class="col-md-9">
                                    <input type="text" id="userJobTitle" class="form-control" maxlength="255"
                                        placeholder="e.g. OHS Manager">
                                    <small class="form-hint">Refreshed from the directory at every sign-in. The roles marked
                                        "by job title" follow it.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="companySelect" class="col-md-3 col-form-label required">Company</label>
                                <div class="col-md-9">
                                    <select id="companySelect" class="form-select" required>
                                        <option></option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->code }} - {{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label class="col-md-3 col-form-label">Status</label>
                                <div class="col-md-9 d-flex align-items-center">
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="userActive" checked>
                                        <span class="form-check-label">Active (allowed to sign in)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label class="col-md-3 col-form-label">Role</label>
                                <div class="col-md-9">
                                    <div class="table-responsive border rounded">
                                        <table class="table table-sm table-vcenter mb-0">
                                            <thead>
                                                <tr class="mdka-bg-gray-50">
                                                    <th class="py-2 text-center" width="60">
                                                        <input class="form-check-input" type="checkbox" id="selectAllRoles"
                                                            aria-label="Select all roles" />
                                                    </th>
                                                    <th class="py-2">Role</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($roles as $role)
                                                    @php($isByJobTitle = in_array($role->id, $automaticRoleIds, true))
                                                    <tr>
                                                        <td class="text-center">
                                                            <input class="form-check-input user-role-checkbox" type="checkbox"
                                                                value="{{ $role->id }}" aria-label="{{ $role->name }}"
                                                                @disabled($isByJobTitle) />
                                                        </td>
                                                        <td>
                                                            {{ $role->name }}
                                                            @if ($isByJobTitle)
                                                                <span class="badge bg-azure-lt ms-1">by job title</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="form-hint">Roles "by job title" are given from the job title at every
                                        sign-in (Role &amp; Permission); only the others are given here.</small>
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
        const urlUserShow = @json(route('user-management.user-registered.show', ':id'));
        const urlUserStore = @json(route('user-management.user-registered.store'));
        const urlUserUpdate = @json(route('user-management.user-registered.update', ':id'));
        let userModalState = {
            mode: 'add',
            id: null
        };

        $(function() {
            $('#userModal #companySelect').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Company',
                allowClear: true,
                dropdownParent: $('#userModal'),
                width: '100%',
            });

            $('#selectAllRoles').on('click', function() {
                $('.user-role-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
            });

            $('#userModal').on('hidden.bs.modal', resetUserForm);
        });

        function resetUserForm() {
            const form = $('#userForm');
            form[0].reset();
            form.find('#companySelect').val(null).trigger('change');
            form.find('.user-role-checkbox, #selectAllRoles').prop('checked', false);
            form.find('#userActive').prop('checked', true);
        }

        function toggleUserModal(mode, id = null) {
            const modal = $('#userModal');

            userModalState = {
                mode,
                id
            };
            resetUserForm();
            modal.find('.modal-title').text(mode === 'edit' ? 'Edit User' : 'Add New User');

            if (mode !== 'edit') {
                modal.modal('show');

                return;
            }

            $.getJSON(urlUserShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    modal.find('#userName').val(data.name);
                    modal.find('#userEmail').val(data.email);
                    modal.find('#userEmployeeId').val(data.employee_id);
                    modal.find('#userJobTitle').val(data.job_title);
                    modal.find('#companySelect').val(data.company_id).trigger('change');
                    modal.find('#userActive').prop('checked', !!data.is_active);
                    (data.role_id || []).forEach((roleId) => {
                        modal.find(`.user-role-checkbox[value="${parseInt(roleId, 10)}"]`).prop('checked', true);
                    });
                    modal.modal('show');
                })
                .fail(showAjaxError);
        }

        $('#userForm').on('submit', function(event) {
            event.preventDefault();

            const data = {
                name: $('#userName').val().trim(),
                email: $('#userEmail').val().trim(),
                employee_id: $('#userEmployeeId').val().trim(),
                job_title: $('#userJobTitle').val().trim(),
                company_id: $('#companySelect').val(),
                is_active: $('#userActive').is(':checked') ? 1 : 0,
                role_id: $('.user-role-checkbox:checked:not(:disabled)').map((_, element) => element.value).get(),
            };

            const errors = [];

            if (!data.name) {
                errors.push('Name cannot be empty.');
            }

            if (!data.email) {
                errors.push('Email cannot be empty.');
            }

            if (!data.company_id) {
                errors.push('Company must be selected.');
            }

            if (errors.length) {
                showError(errors);

                return;
            }

            const isEdit = userModalState.mode === 'edit';

            saveRecord({
                url: isEdit ? urlUserUpdate.replace(':id', userModalState.id) : urlUserStore,
                method: isEdit ? 'PUT' : 'POST',
                data,
                confirmText: `This action will ${isEdit ? 'update' : 'register'} the user!`,
                onSuccess: () => {
                    $('#userModal').modal('hide');
                    userTable.reload();
                },
            });
        });
    </script>
@endpush
