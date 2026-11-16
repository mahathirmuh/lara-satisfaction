@extends('main.index')

@section('page-title', __('Departments'))

@push('style')
    <style>
        .department-thumb {
            width: 56px;
            height: 40px;
            object-fit: cover;
            border-radius: .375rem;
        }
    </style>
@endpush

@section('content')
    <div class="row g-2">
        {{-- Filter --}}
        <div class="col-12">
            <div class="card" id="departmentFilterCard" style="display: none;">
                <div class="card-header p-2">
                    <h4 class="mb-0">{{ __('Filter') }}</h4>
                </div>
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label for="activeFilter" class="form-label">{{ __('Status') }}</label>
                            <select id="activeFilter" class="form-select">
                                <option value="">{{ __('All status') }}</option>
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <x-global.btn-detail label="" color="green" size="sm" outline="true" id="applyFilterButton"
                                data-bs-toggle="tooltip" data-bs-title="{{ __('Apply Filter') }}">
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
                                <input type="text" id="departmentSearch" class="form-control rounded-3" autocomplete="off"
                                    placeholder="{{ __('Search department or section…') }}" />
                                <x-global.btn-detail label="" color="blue" size="sm" outline="true" id="toggleSearchButton"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Search') }}">
                                    <x-slot:icon><i class="ti ti-search"></i></x-slot:icon>
                                </x-global.btn-detail>
                                <x-global.btn-detail label="" color="purple" size="sm" outline="true" id="toggleFilterButton"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Filter') }}">
                                    <x-slot:icon><i class="ti ti-filter"></i></x-slot:icon>
                                </x-global.btn-detail>
                                <x-global.btn-detail label="" color="red" size="sm" outline="true" id="toggleResetButton"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Reset') }}">
                                    <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                                </x-global.btn-detail>
                            </div>
                            @if ($can['create'])
                                <x-global.btn-detail :label="__('Add Department')" color="blue" size="sm"
                                    onclick="toggleDepartmentModal('add')">
                                    <x-slot:icon><i class="ti ti-plus"></i></x-slot:icon>
                                </x-global.btn-detail>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap" id="departmentTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">{{ __('No') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" width="70">{{ __('Image') }}</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="code">{{ __('Code') }}</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="name">{{ __('Department') }}</th>
                                                <th class="text-capitalize fs-5 py-3">{{ __('Sections') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="sort_order">{{ __('Order') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">{{ __('Questions') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="is_active">{{ __('Status') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">{{ __('Action') }}</th>
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

    @include('MasterData::department.modal')
@endsection

@push('javascript')
    <script>
        const urlDepartmentList = @json(route('master-data.department.list'));
        const urlDepartmentDestroy = @json(route('master-data.department.destroy', ':id'));
        const urlSectionIndex = @json(route('master-data.section.index'));
        const departmentCan = @json($can);
        const canManageSections = @json($canManageSections);

        const departmentColumns = [
            'index',
            {
                key: 'image',
                className: 'text-center',
                render: (item) => item.image_url ?
                    `<img src="${escapeHtml(item.image_url)}" alt="" class="department-thumb">` :
                    `<span class="avatar avatar-sm rounded mdka-bg-${escapeHtml(item.color)}-100 mdka-text-${escapeHtml(item.color)}-600"><i class="ti ti-${escapeHtml(item.icon || 'building')}"></i></span>`,
            },
            {
                key: 'code',
                sortable: true,
                render: (item) => `<span class="badge bg-secondary-lt">${escapeHtml(item.code)}</span>`,
            },
            {
                key: 'name',
                sortable: true,
                render: (item) => `<span class="fw-medium">${escapeHtml(item.name)}</span>`,
            },
            {
                key: 'sections',
                render: (item) => {
                    const chips = item.sections.map((section) => `<span class="badge ${section.is_active ? `mdka-bg-${escapeHtml(item.color)}-100 mdka-text-${escapeHtml(item.color)}-600` : 'bg-secondary-lt text-decoration-line-through'} me-1">${escapeHtml(section.name)}</span>`).join('');
                    const link = canManageSections ? ` <a href="${urlSectionIndex}?department_id=${item.id}" class="small ms-1" data-bs-toggle="tooltip" data-bs-title="${escapeHtml(@json(__('Manage sections')))}"><i class="ti ti-external-link"></i></a>` : '';

                    return (chips || `<span class="text-muted small">${escapeHtml(@json(__('No section yet')))}</span>`) + link;
                },
            },
            {
                key: 'sort_order',
                sortable: true,
                className: 'text-center',
            },
            {
                key: 'questions_count',
                className: 'text-center',
            },
            {
                key: 'is_active',
                sortable: true,
                className: 'text-center',
                render: (item) => item.is_active ?
                    `<span class="status status-green"><span class="status-dot"></span>${escapeHtml(@json(__('Active')))}</span>` :
                    `<span class="status status-gray"><span class="status-dot"></span>${escapeHtml(@json(__('Inactive')))}</span>`,
            },
            {
                key: 'actions',
                className: 'text-center',
                render: (item) => {
                    let html = '<div class="d-inline-flex gap-1">';

                    if (departmentCan.update) {
                        html += `<x-global.btn-detail label="" color="orange" size="sm" onclick="toggleDepartmentModal('edit', ${item.id})" data-bs-toggle="tooltip" data-bs-title="{{ __('Edit') }}">
                            <x-slot:icon><i class="ti ti-edit"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    if (departmentCan.delete && item.questions_count === 0) {
                        html += `<x-global.btn-detail label="" color="red" size="sm" onclick="deleteDepartment(${item.id})" data-bs-toggle="tooltip" data-bs-title="{{ __('Delete') }}">
                            <x-slot:icon><i class="ti ti-trash"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    return html + '</div>';
                },
            },
        ];

        const departmentTable = initTablePage({
            container: '#departmentTable',
            url: urlDepartmentList,
            columns: departmentColumns,
            defaultSort: 'sort_order',
            searchInput: '#departmentSearch',
            searchButton: '#toggleSearchButton',
            resetButton: '#toggleResetButton',
            filters: () => ({
                is_active: $('#activeFilter').val(),
            }),
            onReset: () => {
                $('#activeFilter').val('');
                $('#departmentFilterCard').slideUp(300);
            },
        });

        $(function() {
            departmentTable.reload();

            $('#toggleFilterButton').on('click', () => $('#departmentFilterCard').slideToggle(300));
            $('#applyFilterButton').on('click', () => departmentTable.applyFilters());
        });

        function deleteDepartment(id) {
            deleteRecord({
                url: urlDepartmentDestroy.replace(':id', id),
                confirmText: @js(__('The department, its sections and its image will be deleted.')),
                onSuccess: () => departmentTable.reload(),
            });
        }
    </script>
@endpush
