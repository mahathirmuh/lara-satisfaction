@extends('main.index')

@section('page-title', __('Sections'))

@section('content')
    <div class="row g-2">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
                            <select id="departmentFilter" class="form-select">
                                <option value="">{{ __('All departments') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected($selectedDepartmentId === $department->id)>
                                        {{ $department->is_active ? $department->name : __(':name (inactive)', ['name' => $department->name]) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="activeFilter" class="form-label">{{ __('Status') }}</label>
                            <select id="activeFilter" class="form-select">
                                <option value="">{{ __('All status') }}</option>
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-12 d-flex flex-wrap justify-content-between gap-2">
                            <div class="d-flex gap-2">
                                <input type="text" id="sectionSearch" class="form-control rounded-3" autocomplete="off"
                                    placeholder="{{ __('Search…') }}" />
                                <x-global.btn-detail label="" color="blue" size="sm" outline="true" id="toggleSearchButton"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Search') }}">
                                    <x-slot:icon><i class="ti ti-search"></i></x-slot:icon>
                                </x-global.btn-detail>
                                <x-global.btn-detail label="" color="red" size="sm" outline="true" id="toggleResetButton"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Reset') }}">
                                    <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                                </x-global.btn-detail>
                            </div>
                            @if ($can['create'] && $departments->isNotEmpty())
                                <x-global.btn-detail :label="__('Add Section')" color="blue" size="sm"
                                    onclick="toggleSectionModal('add')">
                                    <x-slot:icon><i class="ti ti-plus"></i></x-slot:icon>
                                </x-global.btn-detail>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100" id="sectionTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">{{ __('No') }}</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="department">{{ __('Department') }}</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="name">{{ __('Section & Role Overview') }}</th>
                                                <th class="text-capitalize fs-5 py-3">{{ __('Pictures') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="sort_order" width="80">{{ __('Order') }}</th>
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

    @include('MasterData::section.modal')
@endsection

@push('javascript')
    <script>
        const urlSectionList = @json(route('master-data.section.list'));
        const urlSectionDestroy = @json(route('master-data.section.destroy', ':id'));
        const sectionCan = @json($can);

        const sectionColumns = [
            'index',
            {
                key: 'department',
                sortable: true,
                className: 'text-nowrap',
                render: (item) => `<span class="d-inline-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded mdka-bg-${escapeHtml(item.department_color)}-100 mdka-text-${escapeHtml(item.department_color)}-600"><i class="ti ti-${escapeHtml(item.department_icon || 'building')}"></i></span>
                    ${escapeHtml(item.department)}
                </span>`,
            },
            {
                key: 'name',
                sortable: true,
                render: (item) => {
                    const overview = item.description || '';
                    const shortOverview = overview.length > 140 ? `${overview.slice(0, 140)}…` : overview;

                    return `<div class="fw-medium">${escapeHtml(item.name)}</div>
                        ${shortOverview ? `<div class="text-secondary small" style="white-space: normal; min-width: 260px">${escapeHtml(shortOverview)}</div>` : `<div class="text-warning small">${escapeHtml(@json(__('No role overview yet')))}</div>`}`;
                },
            },
            {
                key: 'overview',
                className: 'text-nowrap',
                render: (item) => [
                    ['team_photo_url', @json(__('Team picture')), @json(__(':name Team picture')), @json(__('No team picture yet')), 'users-group'],
                    ['work_summary_url', @json(__('Work summary')), @json(__(':name Work summary')), @json(__('No work summary yet')), 'chart-line'],
                ].map(([key, label, title, missing, icon]) => item[key] ?
                    `<a href="${escapeHtml(item[key])}" target="_blank" rel="noopener" data-image-preview data-title="${escapeHtml(title.replace(':name', () => item.name))}" data-bs-toggle="tooltip" data-bs-title="${escapeHtml(label)}"><img src="${escapeHtml(item[key])}" alt="${escapeHtml(label)}" class="rounded border me-1" style="width: 48px; height: 32px; object-fit: cover"></a>` :
                    `<span class="avatar avatar-sm rounded border me-1 text-secondary bg-transparent" data-bs-toggle="tooltip" data-bs-title="${escapeHtml(missing)}"><i class="ti ti-${icon}"></i></span>`
                ).join(''),
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
                className: 'text-center text-nowrap',
                render: (item) => {
                    let html = '<div class="d-inline-flex gap-1">';

                    if (sectionCan.update) {
                        html += `<x-global.btn-detail label="" color="orange" size="sm" onclick="toggleSectionModal('edit', ${item.id})" data-bs-toggle="tooltip" data-bs-title="{{ __('Edit') }}">
                            <x-slot:icon><i class="ti ti-edit"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    if (sectionCan.delete && !item.is_used_in_surveys) {
                        html += `<x-global.btn-detail label="" color="red" size="sm" onclick="deleteSection(${item.id})" data-bs-toggle="tooltip" data-bs-title="{{ __('Delete') }}">
                            <x-slot:icon><i class="ti ti-trash"></i></x-slot:icon>
                        </x-global.btn-detail>`;
                    }

                    return html + '</div>';
                },
            },
        ];

        const sectionTable = initTablePage({
            container: '#sectionTable',
            url: urlSectionList,
            columns: sectionColumns,
            defaultSort: 'department',
            searchInput: '#sectionSearch',
            searchButton: '#toggleSearchButton',
            resetButton: '#toggleResetButton',
            filters: () => ({
                department_id: $('#departmentFilter').val() || null,
                is_active: $('#activeFilter').val(),
            }),
            onReset: () => {
                $('#departmentFilter').val('');
                $('#activeFilter').val('');
            },
        });

        $(function() {
            sectionTable.reload();
            $('#departmentFilter, #activeFilter').on('change', () => sectionTable.applyFilters());
        });

        function deleteSection(id) {
            deleteRecord({
                url: urlSectionDestroy.replace(':id', id),
                onSuccess: () => sectionTable.reload(),
            });
        }
    </script>
@endpush
