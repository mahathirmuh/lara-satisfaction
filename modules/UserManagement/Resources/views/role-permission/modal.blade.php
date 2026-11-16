@push('style')
    <style>
        /* Keep the menu/permission matrix scrollable with a pinned header */
        .menu-permission-wrapper {
            max-height: 340px;
            overflow-y: auto;
        }

        #menuPermissionTable thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background-color: #eef1f6 !important;
            box-shadow: inset 0 -1px 0 #e6e7e9;
        }

        #menuPermissionTable tr.menu-group-row td {
            background-color: #f5f7fb;
        }

        #menuPermissionTable tr.menu-child-row .menu-child-label {
            display: inline-block;
            padding-left: 1.5rem;
            position: relative;
        }

        #menuPermissionTable tr.menu-child-row .menu-child-label::before {
            content: "";
            position: absolute;
            left: 0.5rem;
            top: 50%;
            width: 0.6rem;
            height: 1px;
            background-color: #adb5bd;
        }
    </style>
@endpush

<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="roleModal" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content p-3">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-1">
                <h3 class="modal-title mb-3">Role</h3>
                <form id="roleForm" novalidate>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="row">
                                <label for="roleName" class="col-md-3 col-form-label required">Role</label>
                                <div class="col-md-6">
                                    <input type="text" id="roleName" class="form-control" maxlength="255"
                                        placeholder="Role.." required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="roleTypeSelect" class="col-md-3 col-form-label required">Data Scope</label>
                                <div class="col-md-6">
                                    <select id="roleTypeSelect" class="form-select" required>
                                        @foreach ($scopes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12" id="columnCompany" style="display: none">
                            <div class="row">
                                <label for="roleCompanySelect" class="col-md-3 col-form-label required">Companies</label>
                                <div class="col-md-9">
                                    <select id="roleCompanySelect" class="form-select" multiple="multiple">
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->code }} - {{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-hint">Users and survey results outside these companies are hidden
                                        for this role.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="homePageSelect" class="col-md-3 col-form-label">Home Page</label>
                                <div class="col-md-6">
                                    <select id="homePageSelect" class="form-select">
                                        <option value="">Dashboard (default)</option>
                                        @foreach ($menus as $root)
                                            @if ($root->children->isNotEmpty())
                                                <optgroup label="{{ $root->title }}">
                                                    @foreach ($root->children as $child)
                                                        @if ($child->route)
                                                            <option value="{{ $child->id }}">{{ $child->title }}</option>
                                                        @endif
                                                    @endforeach
                                                </optgroup>
                                            @elseif ($root->route)
                                                <option value="{{ $root->id }}">{{ $root->title }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="roleDepartmentScopeSelect" class="col-md-3 col-form-label required">Survey Results</label>
                                <div class="col-md-6">
                                    <select id="roleDepartmentScopeSelect" class="form-select" required>
                                        @foreach ($departmentScopes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-hint">With "managed" departments, the report only shows the
                                        departments whose manager job titles (Master Data &gt; Departments) match the
                                        user's job title.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <label for="roleJobTitles" class="col-md-3 col-form-label">Job Titles</label>
                                <div class="col-md-9">
                                    <textarea id="roleJobTitles" class="form-control font-monospace" rows="3" maxlength="2000"
                                        placeholder="*Manager&#10;Deputy GM&#10;!Assistant of *"></textarea>
                                    <small class="form-hint" id="roleJobTitlesHint">One job title per line: people whose
                                        directory job title matches get this role at every sign-in. <code>*</code> stands
                                        for any text, <code>!</code> excludes. Leave empty to give the role by hand.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="row">
                                <label class="col-md-3 col-form-label">Menu &amp; Permission</label>
                                <div class="col-md-9">
                                    <div class="table-responsive border rounded menu-permission-wrapper">
                                        <table class="table table-sm table-vcenter mb-0" id="menuPermissionTable">
                                            <thead>
                                                <tr class="mdka-bg-gray-50">
                                                    <th class="py-2">Menu</th>
                                                    <th class="py-2 text-center" width="80">
                                                        Access<br>
                                                        <input class="form-check-input" type="checkbox" id="selectAllMenus"
                                                            data-bs-toggle="tooltip" title="Grant full access to every menu" />
                                                    </th>
                                                    @foreach ($permissions as $permission)
                                                        <th class="py-2 text-center" width="80">{{ $permission->name }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($menus as $menu)
                                                    @foreach ([$menu, ...$menu->children] as $item)
                                                        @php($isChild = $item->parent_id !== null)
                                                        <tr @class([
                                                            'menu-group-row' => !$isChild && $menu->children->isNotEmpty(),
                                                            'menu-child-row' => $isChild,
                                                        ])>
                                                            <td>
                                                                @if ($isChild)
                                                                    <span class="menu-child-label">{{ $item->title }}</span>
                                                                @elseif ($menu->children->isNotEmpty())
                                                                    <i class="ti ti-folder me-1 text-muted"></i>
                                                                    <span class="fw-semibold">{{ $item->title }}</span>
                                                                @else
                                                                    {{ $item->title }}
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                <input class="form-check-input menu-access-checkbox"
                                                                    type="checkbox" value="{{ $item->id }}"
                                                                    data-menu-id="{{ $item->id }}"
                                                                    data-parent-id="{{ $item->parent_id }}"
                                                                    aria-label="Access {{ $item->title }}" />
                                                            </td>
                                                            @foreach ($permissions as $permission)
                                                                <td class="text-center">
                                                                    @if ($item->route)
                                                                        <input class="form-check-input menu-permission-checkbox"
                                                                            type="checkbox" value="{{ $permission->id }}"
                                                                            data-menu-id="{{ $item->id }}"
                                                                            aria-label="{{ $permission->name }} {{ $item->title }}" />
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="form-hint">Access opens the menu page; Read loads its data; Create,
                                        Update and Delete allow the matching actions.</small>
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
        const urlRoleShow = @json(route('user-management.role-permission.show', ':id'));
        const urlRoleStore = @json(route('user-management.role-permission.store'));
        const urlRoleUpdate = @json(route('user-management.role-permission.update', ':id'));
        let roleModalState = {
            mode: 'add',
            id: null
        };

        $(function() {
            $('#roleCompanySelect').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Company',
                dropdownParent: $('#roleModal'),
                width: '100%',
                closeOnSelect: false,
            });

            $('#roleTypeSelect').on('change', toggleCompanyColumn);

            $('#selectAllMenus').on('change', function() {
                const checked = $(this).prop('checked');

                $('.menu-access-checkbox, .menu-permission-checkbox').prop('checked', checked);
                $('.menu-access-checkbox').each(function() {
                    syncPermissionRow($(this).data('menu-id'));
                });
            });

            $(document).on('change', '.menu-access-checkbox', function() {
                const menuId = $(this).data('menu-id');
                const parentId = $(this).data('parent-id');

                // Granting a child grants its group; revoking a group revokes its children.
                if ($(this).prop('checked') && parentId) {
                    $(`.menu-access-checkbox[data-menu-id="${parentId}"]`).prop('checked', true);
                }

                if (!$(this).prop('checked')) {
                    $(`.menu-access-checkbox[data-parent-id="${menuId}"]`).prop('checked', false).each(function() {
                        syncPermissionRow($(this).data('menu-id'));
                    });
                }

                syncPermissionRow(menuId);
            });
        });

        function toggleCompanyColumn() {
            $('#columnCompany').toggle($('#roleTypeSelect').val() === 'COMPANY');
        }

        function syncPermissionRow(menuId) {
            const hasAccess = $(`.menu-access-checkbox[data-menu-id="${menuId}"]`).prop('checked');
            const permissions = $(`.menu-permission-checkbox[data-menu-id="${menuId}"]`);

            permissions.prop('disabled', !hasAccess);

            if (!hasAccess) {
                permissions.prop('checked', false);
            }
        }

        function resetRoleForm() {
            const form = $('#roleForm');
            form[0].reset();
            form.find('#roleJobTitles').prop('disabled', false);
            form.find('#roleCompanySelect').val(null).trigger('change');
            form.find('#selectAllMenus, .menu-access-checkbox, .menu-permission-checkbox').prop('checked', false);
            form.find('.menu-access-checkbox').each(function() {
                syncPermissionRow($(this).data('menu-id'));
            });
            toggleCompanyColumn();
        }

        function toggleRoleModal(mode, id = null) {
            const modal = $('#roleModal');

            roleModalState = {
                mode,
                id
            };
            resetRoleForm();
            modal.find('.modal-title').text(mode === 'edit' ? 'Edit Role' : 'Add New Role');

            if (mode !== 'edit') {
                modal.modal('show');

                return;
            }

            $.getJSON(urlRoleShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    modal.find('#roleName').val(data.name);
                    modal.find('#roleTypeSelect').val(data.func_role);
                    modal.find('#roleCompanySelect').val((data.setting_role || []).map(String)).trigger('change');
                    modal.find('#homePageSelect').val(data.home_page ?? '');
                    modal.find('#roleDepartmentScopeSelect').val(data.department_scope);
                    modal.find('#roleJobTitles').val(data.job_titles || '').prop('disabled', !!data.is_it_admin);
                    toggleCompanyColumn();

                    (data.menu_list || []).forEach((menuId) => {
                        modal.find(`.menu-access-checkbox[data-menu-id="${parseInt(menuId, 10)}"]`).prop('checked', true);
                    });

                    modal.find('.menu-access-checkbox').each(function() {
                        syncPermissionRow($(this).data('menu-id'));
                    });

                    Object.entries(data.menu_permission || {}).forEach(([menuId, permissionIds]) => {
                        permissionIds.forEach((permissionId) => {
                            modal.find(`.menu-permission-checkbox[data-menu-id="${parseInt(menuId, 10)}"][value="${parseInt(permissionId, 10)}"]`)
                                .prop('checked', true);
                        });
                    });

                    modal.modal('show');
                })
                .fail(showAjaxError);
        }

        $('#roleForm').on('submit', function(event) {
            event.preventDefault();

            const menuPermission = {};

            $('.menu-permission-checkbox:checked').each(function() {
                const menuId = $(this).data('menu-id');
                (menuPermission[menuId] = menuPermission[menuId] || []).push($(this).val());
            });

            const data = {
                name: $('#roleName').val().trim(),
                func_role: $('#roleTypeSelect').val(),
                setting_role: $('#roleCompanySelect').val() || [],
                home_page: $('#homePageSelect').val() || null,
                department_scope: $('#roleDepartmentScopeSelect').val(),
                job_titles: $('#roleJobTitles').prop('disabled') ? '' : $('#roleJobTitles').val(),
                menu_list: $('.menu-access-checkbox:checked').map((_, element) => element.value).get(),
                menu_permission: menuPermission,
            };

            const errors = [];

            if (!data.name) {
                errors.push('Role name cannot be empty.');
            }

            if (data.func_role === 'COMPANY' && !data.setting_role.length) {
                errors.push('Select at least one company for a COMPANY scoped role.');
            }

            if (errors.length) {
                showError(errors);

                return;
            }

            const isEdit = roleModalState.mode === 'edit';

            saveRecord({
                url: isEdit ? urlRoleUpdate.replace(':id', roleModalState.id) : urlRoleStore,
                method: isEdit ? 'PUT' : 'POST',
                data,
                confirmText: `This action will ${isEdit ? 'update' : 'create'} the role!`,
                onSuccess: () => {
                    $('#roleModal').modal('hide');
                    roleTable.reload();
                },
            });
        });
    </script>
@endpush
