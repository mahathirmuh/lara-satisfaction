@extends('main.index')

@push('style')
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/select2.min.css') }}?v=4.1.0-rc.0" />
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/select2-bootstrap.min.css') }}" />
@endpush

@section('page-title', 'User Registered')

@section('content')
    <div class="row g-2">
        {{-- Filter --}}
        <div class="col-12">
            @include('UserManagement::user-registered.filter')
        </div>

        {{-- Table --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-12 d-flex flex-wrap justify-content-between gap-2">
                            <div class="d-flex gap-2">
                                <input type="text" id="userSearch" class="form-control rounded-3" autocomplete="off"
                                    placeholder="Search name, email, employee ID…" />
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
                                <x-global.btn-detail label="Add User" color="blue" size="sm"
                                    onclick="toggleUserModal('add')">
                                    <x-slot:icon><i class="ti ti-plus"></i></x-slot:icon>
                                </x-global.btn-detail>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap" id="userTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">No</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="name">Name</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="employee_id">Employee ID</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="job_title">Job Title</th>
                                                <th class="text-capitalize fs-5 py-3">Company</th>
                                                <th class="text-capitalize fs-5 py-3">Role</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="is_active">Status</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="last_login_at">Last Login</th>
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

    @include('UserManagement::user-registered.modal')
@endsection

@push('javascript')
    <script src="{{ asset('assets/libs/select2/select2.min.js') }}?v=4.1.0-rc.0"></script>
    <script>
        const urlUserList = @json(route('user-management.user-registered.list'));
        const urlUserDestroy = @json(route('user-management.user-registered.destroy', ':id'));
        const userCan = @json($can);

        const userColumns = [
            'index',
            {
                key: 'name',
                sortable: true,
                render: (item) => `<div class="fw-medium">${escapeHtml(item.name)}</div><div class="text-secondary small">${escapeHtml(item.email)}</div>`,
            },
            {
                key: 'employee_id',
                sortable: true,
                render: (item) => escapeHtml(item.employee_id || '-'),
            },
            {
                key: 'job_title',
                sortable: true,
                render: (item) => `<span class="text-wrap">${escapeHtml(item.job_title || '-')}</span>`,
            },
            {
                key: 'company',
                render: (item) => escapeHtml(item.company || '-'),
            },
            {
                key: 'role',
                render: (item) => escapeHtml(item.role || '-'),
            },
            {
                key: 'is_active',
                sortable: true,
                className: 'text-center',
                render: (item) => item.is_active ?
                    '<span class="status status-green"><span class="status-dot"></span>Active</span>' :
                    '<span class="status status-gray"><span class="status-dot"></span>Inactive</span>',
            },
            {
                key: 'last_login_at',
                sortable: true,
                render: (item) => item.last_login_at ? escapeHtml(formatDateTime(item.last_login_at)) : '<span class="text-muted">Never</span>',
            },
            {
                key: 'actions',
                className: 'text-center',
                render: (item) => {
                    let html = '<div class="d-inline-flex gap-1">';

                    if (userCan.update) {
                        html += `<x-global.btn-detail label="" color="orange" size="sm" onclick="toggleUserModal('edit', ${item.id})" data-bs-toggle="tooltip" data-bs-title="Edit">
                            <x-slot:icon><i class="ti ti-edit"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    if (userCan.delete && !item.is_self) {
                        html += `<x-global.btn-detail label="" color="red" size="sm" onclick="deleteUser(${item.id})" data-bs-toggle="tooltip" data-bs-title="Delete">
                            <x-slot:icon><i class="ti ti-trash"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    return html + '</div>';
                },
            },
        ];

        const userTable = initTablePage({
            container: '#userTable',
            url: urlUserList,
            columns: userColumns,
            defaultSort: 'name',
            searchInput: '#userSearch',
            searchButton: '#toggleSearchButton',
            resetButton: '#toggleResetButton',
            filters: () => userFilters(),
            onReset: () => resetUserFilters(),
        });

        $(function() {
            userTable.reload();
            $('#toggleFilterButton').on('click', () => $('#userFilterCard').slideToggle(300));
        });

        function deleteUser(id) {
            deleteRecord({
                url: urlUserDestroy.replace(':id', id),
                confirmText: 'The user will lose access to the application.',
                onSuccess: () => userTable.reload(),
            });
        }
    </script>
@endpush
