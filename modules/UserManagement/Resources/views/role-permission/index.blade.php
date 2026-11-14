@extends('main.index')

@push('style')
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/select2.min.css') }}?v=4.1.0-rc.0" />
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/select2-bootstrap.min.css') }}" />
@endpush

@section('page-title', 'Role & Permission')

@section('content')
    <div class="row g-2">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-12 d-flex flex-wrap justify-content-between gap-2">
                            <div class="d-flex gap-2">
                                <input type="text" id="roleSearch" class="form-control rounded-3" autocomplete="off"
                                    placeholder="Search role…" />
                                <x-global.btn-detail label="" color="blue" size="sm" outline="true" id="toggleSearchButton"
                                    data-bs-toggle="tooltip" data-bs-title="Search">
                                    <x-slot:icon><i class="ti ti-search"></i></x-slot:icon>
                                </x-global.btn-detail>
                                <x-global.btn-detail label="" color="red" size="sm" outline="true" id="toggleResetButton"
                                    data-bs-toggle="tooltip" data-bs-title="Reset">
                                    <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                                </x-global.btn-detail>
                            </div>
                            @if ($can['create'])
                                <x-global.btn-detail label="Add Role" color="blue" size="sm"
                                    onclick="toggleRoleModal('add')">
                                    <x-slot:icon><i class="ti ti-plus"></i></x-slot:icon>
                                </x-global.btn-detail>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap" id="roleTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">No</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="name">Role</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="func_role">Data Scope</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">Menus</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="users_count">Users</th>
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

    @include('UserManagement::role-permission.modal')
@endsection

@push('javascript')
    <script src="{{ asset('assets/libs/select2/select2.min.js') }}?v=4.1.0-rc.0"></script>
    <script>
        const urlRoleList = @json(route('user-management.role-permission.list'));
        const urlRoleDestroy = @json(route('user-management.role-permission.destroy', ':id'));
        const roleCan = @json($can);

        const roleColumns = [
            'index',
            {
                key: 'name',
                sortable: true,
                render: (item) => `<span class="fw-medium">${escapeHtml(item.name)}</span>
                    ${item.is_protected ? '<span class="badge bg-red-lt ms-1">Super Admin</span>' : ''}
                    ${item.is_default ? '<span class="badge bg-blue-lt ms-1">Default SSO role</span>' : ''}`,
            },
            {
                key: 'func_role',
                sortable: true,
                render: (item) => `<span class="badge bg-secondary-lt me-1">${escapeHtml(item.func_role)}</span>
                    <span class="text-secondary small text-wrap">${escapeHtml(item.scope)}</span>`,
            },
            {
                key: 'menus_count',
                className: 'text-center',
            },
            {
                key: 'users_count',
                sortable: true,
                className: 'text-center',
            },
            {
                key: 'actions',
                className: 'text-center',
                render: (item) => {
                    let html = '<div class="d-inline-flex gap-1">';

                    if (roleCan.update) {
                        html += `<x-global.btn-detail label="" color="orange" size="sm" onclick="toggleRoleModal('edit', ${item.id})" data-bs-toggle="tooltip" data-bs-title="Edit">
                            <x-slot:icon><i class="ti ti-edit"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    if (roleCan.delete && !item.is_protected && !item.is_default && item.users_count === 0) {
                        html += `<x-global.btn-detail label="" color="red" size="sm" onclick="deleteRole(${item.id})" data-bs-toggle="tooltip" data-bs-title="Delete">
                            <x-slot:icon><i class="ti ti-trash"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    return html + '</div>';
                },
            },
        ];

        const roleTable = initTablePage({
            container: '#roleTable',
            url: urlRoleList,
            columns: roleColumns,
            defaultSort: 'id',
            searchInput: '#roleSearch',
            searchButton: '#toggleSearchButton',
            resetButton: '#toggleResetButton',
        });

        $(function() {
            roleTable.reload();
        });

        function deleteRole(id) {
            deleteRecord({
                url: urlRoleDestroy.replace(':id', id),
                onSuccess: () => roleTable.reload(),
            });
        }
    </script>
@endpush
