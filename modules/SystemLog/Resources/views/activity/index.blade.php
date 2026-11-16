@extends('main.index')

@section('page-title', __('Activity Log'))

@section('content')
    <div class="row g-2">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <input type="text" id="activitySearch" class="form-control rounded-3" style="max-width: 280px"
                                autocomplete="off" placeholder="{{ __('Search route, URL or IP…') }}" />
                            <select id="methodFilter" class="form-select" style="max-width: 160px" aria-label="{{ __('Method') }}">
                                <option value="">{{ __('All methods') }}</option>
                                @foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                                    <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                            <x-global.btn-detail label="" color="red" size="sm" outline="true" id="toggleResetButton"
                                data-bs-toggle="tooltip" data-bs-title="{{ __('Reset') }}">
                                <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                            </x-global.btn-detail>
                        </div>
                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap" id="activityTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">{{ __('No') }}</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="created_at">{{ __('Time') }}</th>
                                                <th class="text-capitalize fs-5 py-3">{{ __('User') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="method">{{ __('Method') }}</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="route_name">{{ __('Route') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center" data-key="status_code">{{ __('Status') }}</th>
                                                <th class="text-capitalize fs-5 py-3">{{ __('IP') }}</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">{{ __('Detail') }}</th>
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

    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Activity Detail') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <pre class="mb-0 small" id="logDetailContent" style="white-space: pre-wrap"></pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('javascript')
    <script>
        const urlActivityList = @json(route('system-log.activity.list'));
        const urlActivityShow = @json(route('system-log.activity.show', ':id'));
        const methodColors = {
            POST: 'green',
            PUT: 'orange',
            PATCH: 'orange',
            DELETE: 'red'
        };

        const activityColumns = [
            'index',
            {
                key: 'created_at',
                sortable: true,
                render: (item) => escapeHtml(formatDateTime(item.created_at)),
            },
            {
                key: 'user'
            },
            {
                key: 'method',
                sortable: true,
                className: 'text-center',
                render: (item) => `<span class="badge bg-${methodColors[item.method] || 'secondary'}-lt">${escapeHtml(item.method)}</span>`,
            },
            {
                key: 'route_name',
                sortable: true,
                render: (item) => `<div>${escapeHtml(item.route_name || '-')}</div><div class="text-secondary small text-truncate" style="max-width: 360px">${escapeHtml(item.url)}</div>`,
            },
            {
                key: 'status_code',
                sortable: true,
                className: 'text-center',
                render: (item) => `<span class="badge bg-${item.status_code >= 400 ? 'red' : 'green'}-lt">${escapeHtml(item.status_code ?? '-')}</span>`,
            },
            {
                key: 'ip'
            },
            {
                key: 'detail',
                className: 'text-center',
                render: (item) => `<x-global.btn-detail label="" color="blue" size="sm" outline="true" onclick="showActivity(${item.id})" data-bs-toggle="tooltip" data-bs-title="{{ __('Detail') }}">
                    <x-slot:icon><i class="ti ti-eye"></i></x-slot:icon>
                </x-global.btn-detail>`,
            },
        ];

        const activityTable = initTablePage({
            container: '#activityTable',
            url: urlActivityList,
            columns: activityColumns,
            defaultSort: 'created_at',
            defaultDir: 'desc',
            searchInput: '#activitySearch',
            resetButton: '#toggleResetButton',
            filters: () => ({
                method: $('#methodFilter').val() || null
            }),
            onReset: () => $('#methodFilter').val(''),
        });

        $(function() {
            activityTable.reload();
            $('#methodFilter').on('change', () => activityTable.applyFilters());
        });

        function showActivity(id) {
            $.getJSON(urlActivityShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    $('#logDetailContent').text(JSON.stringify(data, null, 2));
                    $('#logDetailModal').modal('show');
                })
                .fail(showAjaxError);
        }
    </script>
@endpush
