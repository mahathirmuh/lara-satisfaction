@extends('main.index')

@section('page-title', 'Error Log')

@section('content')
    <div class="row g-2">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <input type="text" id="errorSearch" class="form-control rounded-3" style="max-width: 320px"
                                autocomplete="off" placeholder="Search message, exception or URL…" />
                            <x-global.btn-detail label="" color="red" size="sm" outline="true" id="toggleResetButton"
                                data-bs-toggle="tooltip" data-bs-title="Reset">
                                <x-slot:icon><i class="ti ti-restore"></i></x-slot:icon>
                            </x-global.btn-detail>
                        </div>
                        <div class="col-12">
                            <div class="card overflow-hidden mdka-border-gray-200">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-vcenter mdka-border-gray-100" id="errorTable">
                                        <thead>
                                            <tr class="mdka-bg-gray-50">
                                                <th class="text-capitalize fs-5 py-3 text-center" width="30">No</th>
                                                <th class="text-capitalize fs-5 py-3 text-nowrap" data-key="created_at">Time</th>
                                                <th class="text-capitalize fs-5 py-3" data-key="exception">Exception</th>
                                                <th class="text-capitalize fs-5 py-3">Message</th>
                                                <th class="text-capitalize fs-5 py-3">Location</th>
                                                <th class="text-capitalize fs-5 py-3 text-center">Detail</th>
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

    <div class="modal fade" id="errorDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre class="mb-0 small" id="errorDetailContent" style="white-space: pre-wrap"></pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('javascript')
    <script>
        const urlErrorList = @json(route('system-log.error.list'));
        const urlErrorShow = @json(route('system-log.error.show', ':id'));

        const errorColumns = [
            'index',
            {
                key: 'created_at',
                sortable: true,
                className: 'text-nowrap',
                render: (item) => escapeHtml(formatDateTime(item.created_at)),
            },
            {
                key: 'exception',
                sortable: true,
                render: (item) => `<span class="badge bg-red-lt">${escapeHtml(item.exception)}</span>`,
            },
            {
                key: 'message',
                render: (item) => `<div style="min-width: 260px">${escapeHtml(item.message)}</div>`,
            },
            {
                key: 'location',
                className: 'text-nowrap text-secondary small',
            },
            {
                key: 'detail',
                className: 'text-center',
                render: (item) => `<x-global.btn-detail label="" color="blue" size="sm" outline="true" onclick="showErrorDetail(${item.id})" data-bs-toggle="tooltip" data-bs-title="Detail">
                    <x-slot:icon><i class="ti ti-eye"></i></x-slot:icon>
                </x-global.btn-detail>`,
            },
        ];

        const errorTable = initTablePage({
            container: '#errorTable',
            url: urlErrorList,
            columns: errorColumns,
            defaultSort: 'created_at',
            defaultDir: 'desc',
            searchInput: '#errorSearch',
            resetButton: '#toggleResetButton',
        });

        $(function() {
            errorTable.reload();
        });

        function showErrorDetail(id) {
            $.getJSON(urlErrorShow.replace(':id', id))
                .done(({
                    data
                }) => {
                    const trace = data.stack_trace;
                    delete data.stack_trace;
                    $('#errorDetailContent').text(JSON.stringify(data, null, 2) + '\n\nStack trace:\n' + (trace || '-'));
                    $('#errorDetailModal').modal('show');
                })
                .fail(showAjaxError);
        }
    </script>
@endpush
