<script src="{{ asset('assets/libs/jquery/jquery.min.js') }}?v=3.7.1"></script>
<script src="{{ asset('assets/libs/sweetalert2/sweetalert2.all.min.js') }}?v=11.26.25"></script>
<script src="{{ asset('assets/js/apps.min.js') }}?v={{ config('merdeka.app_version') }}" defer></script>

<script>
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const timezone = @json(config('app.timezone'));

    /**
     * Locale for Intl date/number formatting: the interface language
     * (window.appLocale), while English keeps the given English variant.
     */
    function intlLocale(englishLocale = 'en-GB') {
        const locale = window.appLocale || 'en';

        return locale.startsWith('en') ? englishLocale : locale;
    }

    /**
     * Fill the :placeholders of a translated text in one pass, longest name
     * first like Laravel (so ":total" is never read as ":to").
     */
    function replacePlaceholders(text, replacements = {}) {
        const keys = Object.keys(replacements).sort((a, b) => b.length - a.length);

        if (!keys.length) {
            return String(text);
        }

        return String(text).replace(new RegExp(`:(${keys.join('|')})`, 'g'), (match, key) => String(replacements[key]));
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
        }
    });

    /**
     * Escape a value for safe insertion into HTML markup.
     */
    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Delay a callback until the user stopped typing.
     */
    function debounce(func, timeout = 350) {
        let timer;

        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), timeout);
        };
    }

    /**
     * Initialise Bootstrap tooltips inside a container.
     */
    function initTooltips(scope = document) {
        $(scope).find('[data-bs-toggle="tooltip"]').each(function() {
            $(this).tooltip({
                trigger: 'hover'
            });
        }).on('click', function() {
            $(this).tooltip('hide');
        });
    }

    /**
     * Remove locally saved survey drafts (called on logout so answers never
     * stay behind on a shared computer).
     */
    function clearSurveyDrafts() {
        try {
            Object.keys(localStorage)
                .filter((key) => key.startsWith('survey-draft:'))
                .forEach((key) => localStorage.removeItem(key));
        } catch (error) {
            // Storage unavailable: nothing to clear.
        }
    }

    function logout(event) {
        event.preventDefault();
        clearSurveyDrafts();
        document.getElementById('logout-form').submit();
    }

    $(function() {
        initTooltips();
    });
</script>

{{-- Table helpers (render rows, pagination and sorting from a JSON list endpoint) --}}
<script>
    function renderTableWithFeatures({
        container,
        data = [],
        columns = [],
        currentPage = 1,
        limit = 10,
        emptyText = @json(__('No data found')),
        loading = false,
        meta = null,
        onPageChange = () => {},
        onLimitChange = () => {},
        onSortChange = () => {},
        currentSortKey = null,
        currentSortDir = 'asc',
        paginationContainer = '.pagination-container',
    }) {
        renderTable({
            container,
            data,
            columns,
            currentPage,
            limit,
            emptyText,
            loading,
        });

        if (!loading && meta) {
            renderPagination({
                meta,
                limit,
                container: paginationContainer,
                onPageChange,
                onLimitChange,
            });
        }

        if (!loading) {
            initSorting({
                container,
                columns,
                currentSortKey,
                currentSortDir,
                onSortChange,
            });
            initTooltips(container);
        }
    }

    /**
     * Render rows into the <tbody> of a table.
     *
     * Columns: 'index' (row number), a string key (escaped value) or an object
     * { key, className, render }. A custom render() returns HTML and MUST
     * escape every dynamic value with escapeHtml().
     */
    function renderTable({
        container,
        data = [],
        columns = [],
        currentPage = 1,
        limit = 10,
        emptyText = @json(__('No data found')),
        loading = false,
    }) {
        const tbody = $(`${container} tbody`);

        if (loading) {
            tbody.html(`<tr><td colspan="${columns.length}" class="text-center text-muted py-4">
                <span class="spinner-border spinner-border-sm me-2"></span>${escapeHtml(@json(__('Loading...')))}</td></tr>`);
            return;
        }

        if (!data || data.length === 0) {
            tbody.html(`<tr><td colspan="${columns.length}" class="text-center text-muted py-4">${escapeHtml(emptyText)}</td></tr>`);
            return;
        }

        const startNumber = (currentPage - 1) * limit + 1;
        let html = '';

        data.forEach((item, index) => {
            html += '<tr>';
            columns.forEach((col) => {
                if (col === 'index') {
                    html += `<td class="text-center">${startNumber + index}</td>`;
                } else if (typeof col === 'object') {
                    const value = typeof col.render === 'function' ?
                        col.render(item, index) :
                        escapeHtml(item[col.key] ?? '');
                    html += `<td class="${col.className || ''}">${value}</td>`;
                } else {
                    html += `<td>${escapeHtml(item[col] ?? '')}</td>`;
                }
            });
            html += '</tr>';
        });

        tbody.html(html);
    }

    /**
     * Render pagination controls from Laravel paginator meta.
     */
    function renderPagination({
        meta,
        container = '.pagination-container',
        onPageChange = () => {},
        onLimitChange = () => {},
        limit = 10,
    }) {
        const el = $(container);

        if (!meta || meta.total === 0) {
            return el.hide();
        }

        const pageLink = (page) => `<a data-page="${page}" class="btn-pagination cursor-pointer text-decoration-none ${meta.current_page === page ? 'mdka-btn-blue-500 text-white' : ''}">${page}</a>`;
        const showingText = replacePlaceholders(@json(__('Showing :from - :to of :total')), {
            from: meta.from || 0,
            to: meta.to || 0,
            total: meta.total,
        });

        let html = `<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-row justify-content-start align-items-center gap-3">`;

        html += `<a class="btn-previous text-decoration-none ${meta.current_page <= 1 ? 'pe-none disabled' : 'cursor-pointer'}" data-page="${meta.current_page - 1}"><i class="ti ti-chevron-left"></i></a>`;
        html += pageLink(1);

        if (meta.current_page > 4) {
            html += '<span>...</span>';
        }

        if (meta.last_page > 2) {
            const start = Math.max(meta.current_page - 2, 2);
            const end = Math.min(meta.current_page + 2, meta.last_page - 1);

            for (let i = start; i <= end; i++) {
                html += pageLink(i);
            }
        }

        if (meta.current_page < meta.last_page - 3) {
            html += '<span>...</span>';
        }

        if (meta.last_page > 1) {
            html += pageLink(meta.last_page);
        }

        html += `<a class="btn-next text-decoration-none ${meta.current_page >= meta.last_page ? 'pe-none disabled' : 'cursor-pointer'}" data-page="${meta.current_page + 1}"><i class="ti ti-chevron-right"></i></a>`;

        html += `</div>
            <div class="d-flex flex-row justify-content-end align-items-center gap-2">
                <div class="mb-0 text-nowrap">${escapeHtml(showingText)}</div>
                <div class="mb-0 text-nowrap">${escapeHtml(@json(__('Show :')))}</div>
                <select class="form-select pagination-limit">
                    ${[10, 25, 50, 100].map((size) => `<option value="${size}" ${limit == size ? 'selected' : ''}>${size}</option>`).join('')}
                </select>
            </div>
        </div>`;

        el.html(html).show();

        el.find('.btn-pagination, .btn-previous, .btn-next').off('click').on('click', function() {
            const page = $(this).data('page');

            if (page) {
                onPageChange(page);
            }
        });

        el.find('.pagination-limit').off('change').on('change', function() {
            onLimitChange(parseInt($(this).val(), 10));
        });
    }

    /**
     * Wire a Merdeka table page: JSON list endpoint, paging, sorting, search,
     * reset and extra filters. Returns { reload, reset, state }.
     */
    function initTablePage({
        container,
        url,
        columns,
        defaultSort = 'id',
        defaultDir = 'asc',
        searchInput = null,
        searchButton = null,
        resetButton = null,
        filters = () => ({}),
        onReset = () => {},
        emptyText = @json(__('No data found')),
    }) {
        const state = {
            currentPage: 1,
            limit: 10,
            search: '',
            orderBy: defaultSort,
            orderDir: defaultDir,
        };

        function reload() {
            renderTableWithFeatures({
                container,
                columns,
                currentPage: state.currentPage,
                limit: state.limit,
                loading: true,
            });

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                data: {
                    page: state.currentPage,
                    paginated: state.limit,
                    search: state.search,
                    orderBy: state.orderBy,
                    orderDir: state.orderDir,
                    ...filters(),
                },
                success: (response) => {
                    renderTableWithFeatures({
                        container,
                        data: response.data,
                        columns,
                        currentPage: state.currentPage,
                        limit: state.limit,
                        meta: response.meta,
                        emptyText,
                        currentSortKey: state.orderBy,
                        currentSortDir: state.orderDir,
                        onPageChange: (page) => {
                            state.currentPage = page;
                            reload();
                        },
                        onLimitChange: (limit) => {
                            state.limit = limit;
                            state.currentPage = 1;
                            reload();
                        },
                        onSortChange: (key, dir) => {
                            state.orderBy = key;
                            state.orderDir = dir;
                            reload();
                        },
                    });
                },
                error: (xhr) => {
                    renderTableWithFeatures({
                        container,
                        columns,
                        emptyText: ajaxErrorMessages(xhr)[0] || @json(__('Error loading data. Please try again.')),
                    });
                },
            });
        }

        function applySearch() {
            state.search = $(searchInput).val() || '';
            state.currentPage = 1;
            reload();
        }

        function reset() {
            state.currentPage = 1;
            state.limit = 10;
            state.search = '';
            state.orderBy = defaultSort;
            state.orderDir = defaultDir;

            if (searchInput) {
                $(searchInput).val('');
            }

            onReset();
            reload();
        }

        if (searchInput) {
            $(searchInput).on('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applySearch();
                }
            }).on('input', debounce(applySearch, 500));
        }

        if (searchButton) {
            $(searchButton).on('click', applySearch);
        }

        if (resetButton) {
            $(resetButton).on('click', reset);
        }

        return {
            reload,
            reset,
            state,
            applyFilters: () => {
                state.currentPage = 1;
                reload();
            },
        };
    }

    /**
     * Confirm, then send a create/update request; shows validation errors.
     * `data` may be a plain object or a FormData (for file uploads).
     */
    function saveRecord({
        url,
        method = 'POST',
        data = {},
        confirmText = @json(__('Data will be saved!')),
        onSuccess = () => {},
    }) {
        return confirmSave(confirmText).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            const isFormData = data instanceof FormData;
            const payload = isFormData ? data : {
                ...data
            };

            if (method !== 'POST') {
                isFormData ? payload.set('_method', method) : payload._method = method;
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: payload,
                dataType: 'json',
                processData: !isFormData,
                contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                success: (response) => {
                    toastSuccess(response.message || @json(__('Data has been saved.')));
                    onSuccess(response);
                },
                error: showAjaxError,
            });
        });
    }

    /**
     * Preview an image chosen in a file input inside an <img>.
     */
    function previewImage(input, imageSelector) {
        const file = input.files && input.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => $(imageSelector).attr('src', event.target.result).removeClass('d-none');
        reader.readAsDataURL(file);
    }

    /**
     * Show an image in the preview popup (title and caption are set as text).
     */
    function showImagePreview(url, title = '', caption = '') {
        const modal = $('#imagePreviewModal');

        modal.find('.image-preview-body').removeClass('is-zoomed');
        modal.find('#imagePreviewImage').attr({
            src: url,
            alt: title || @json(__('Image')),
        });
        modal.find('#imagePreviewTitle').text(title || @json(__('Image')));
        modal.find('#imagePreviewCaption').text(caption || '');
        modal.modal('show');
    }

    // Links marked data-image-preview open in the popup; Ctrl/middle click still opens a new tab.
    $(document).on('click', 'a[data-image-preview]', function(event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
            return;
        }

        event.preventDefault();
        showImagePreview($(this).attr('href'), $(this).data('title'), $(this).data('caption'));
    });

    $(document).on('click', '#imagePreviewImage', function() {
        $(this).closest('.image-preview-body').toggleClass('is-zoomed');
    });

    /**
     * Confirm, then send a DELETE request.
     */
    function deleteRecord({
        url,
        confirmText = @json(__('This action cannot be undone!')),
        onSuccess = () => {},
    }) {
        return confirmDelete(confirmText).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _method: 'DELETE'
                },
                dataType: 'json',
                success: (response) => {
                    toastSuccess(response.message || @json(__('Data has been deleted.')));
                    onSuccess(response);
                },
                error: showAjaxError,
            });
        });
    }

    /**
     * Make headers of sortable columns clickable (th[data-key]).
     */
    function initSorting({
        container,
        columns = [],
        currentSortKey = null,
        currentSortDir = 'asc',
        onSortChange = () => {},
    }) {
        const table = $(container);

        columns.forEach((col) => {
            if (typeof col !== 'object' || !col.key || !col.sortable) {
                return;
            }

            const th = table.find(`th[data-key="${col.key}"]`);

            if (!th.length) {
                return;
            }

            if (!th.data('label')) {
                th.data('label', th.text().trim());
            }

            let icon = '<i class="ti ti-arrows-sort text-muted"></i>';

            if (currentSortKey === col.key) {
                icon = currentSortDir === 'asc' ? '<i class="ti ti-chevron-up"></i>' : '<i class="ti ti-chevron-down"></i>';
            }

            th.html(`<div class="d-flex align-items-center w-100 gap-1">
                    <span class="flex-grow-1 text-wrap ${col.headerClassName || ''}">${escapeHtml(th.data('label'))}</span>
                    <span class="small">${icon}</span>
                </div>`);

            th.addClass('sortable').off('click').on('click', () => {
                const newDir = (currentSortKey === col.key && currentSortDir === 'asc') ? 'desc' : 'asc';
                onSortChange(col.key, newDir);
            });
        });
    }
</script>

{{-- Dialog helpers --}}
<script>
    /**
     * Show plain-text error messages (escaped) in a SweetAlert dialog.
     */
    const showError = (errors, title = @json(__('Sorry!'))) => {
        const messages = Array.isArray(errors) ? errors : [errors];

        Swal.fire({
            icon: 'error',
            title: title,
            html: messages.map(escapeHtml).join('<br>'),
        });
    };

    /**
     * Extract user-facing messages from a failed jQuery AJAX request.
     */
    function ajaxErrorMessages(xhr) {
        const json = xhr.responseJSON || {};

        if (json.errors) {
            return Object.values(json.errors).flat();
        }

        if (json.message) {
            return [json.message];
        }

        return [@json(__('Something went wrong. Please try again.'))];
    }

    const showAjaxError = (xhr) => showError(ajaxErrorMessages(xhr));

    /**
     * Ask for confirmation before a save action (Merdeka style).
     */
    function confirmSave(text = @json(__('Data will be saved!'))) {
        return Swal.fire({
            title: escapeHtml(@json(__('Is the data correct?'))),
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-check"></i> ' + escapeHtml(@json(__('Save'))),
            cancelButtonText: '<i class="ti ti-x"></i> ' + escapeHtml(@json(__('Cancel'))),
            reverseButtons: true,
        });
    }

    /**
     * Ask for confirmation before a destructive action.
     */
    function confirmDelete(text = @json(__('This action cannot be undone!'))) {
        return Swal.fire({
            title: escapeHtml(@json(__('Are you sure?'))),
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-check"></i> ' + escapeHtml(@js(__('Yes, delete it!'))),
            cancelButtonText: '<i class="ti ti-x"></i> ' + escapeHtml(@json(__('Cancel'))),
            reverseButtons: true,
        });
    }

    /**
     * Small success notification.
     */
    function toastSuccess(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
        });
    }

    function formatDate(dateTimeString) {
        if (!dateTimeString) {
            return '';
        }

        return new Date(dateTimeString).toLocaleDateString(intlLocale('en-GB'), {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: timezone,
        });
    }

    function formatDateTime(dateTimeString) {
        if (!dateTimeString) {
            return '';
        }

        return new Date(dateTimeString).toLocaleString(intlLocale('en-GB'), {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: timezone,
        });
    }
</script>
@stack('javascript')
