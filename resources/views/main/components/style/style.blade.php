@php($assetVersion = config('merdeka.app_version'))
<link href="{{ asset('assets/css/apps.min.css') }}?v={{ $assetVersion }}" rel="stylesheet" />
<link href="{{ asset('assets/libs/tabler-icons/tabler-icons.min.css') }}?v=2.36.0" rel="stylesheet" />
<link href="{{ asset('assets/css/fonts/fonts.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/custom.css') }}?v={{ $assetVersion }}" rel="stylesheet" />
{{-- The design layer taken from MTI Radio Management: the sidebar and its icon rail, the top bar, the path to the page,
     the footer and their motion are in it. --}}
<link href="{{ asset('assets/css/merdeka-ui.css') }}?v={{ @filemtime(public_path('assets/css/merdeka-ui.css')) }}" rel="stylesheet" />
@stack('style')
<style>
    body {
        font-feature-settings: "cv03", "cv04", "cv11";
        font-size: 13px;
    }

    tbody {
        font-size: 0.75rem !important;
    }

    .form-control,
    .form-control-plaintext,
    .form-select,
    .input-group-text,
    .col-form-label {
        background-position: right .35rem center;
        background-size: 14px 9px;
        padding: .375rem .75rem;
    }

    .select2-container .select2-selection--single .select2-selection__rendered,
    .form-control,
    .form-select,
    .form-control-plaintext,
    .input-group-text,
    .form-check-input,
    .form-check-label,
    input[type="date"] {
        font-size: 0.75rem;
    }

    .form-check-label {
        padding-top: 0.175rem;
    }

    .form-label,
    .col-form-label {
        font-size: 0.75rem;
    }

    .select2-container .select2-selection--single .select2-selection__placeholder,
    input::placeholder,
    input[type="date"]::placeholder,
    textarea::placeholder {
        font-style: italic !important;
    }

    .select2-container--bootstrap-5 .select2-selection__rendered {
        line-height: 22px;
    }

    .nav-pills .nav-link.active,
    .nav-pills .show>.nav-link {
        color: var(--tblr-white);
        background-color: var(--tblr-primary);
    }

    .dropdown-item {
        font-size: 0.8rem;
    }

    .table thead th {
        background-color: transparent;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    th.sortable {
        cursor: pointer;
        user-select: none;
    }

    .pagination-limit {
        width: 72px;
    }

    @media (min-width: 992px) {
        .modal-xxl {
            --tblr-modal-width: 1140px;
        }
    }

    /* The bell beside the menu button on a phone: the sidebar clips what overflows it, so the panel is pinned to the
       window. */
    @media (max-width: 991.98px) {
        aside .notification-bell .dropdown-menu {
            position: fixed !important;
            inset: 4.25rem .75rem auto .75rem !important;
            width: auto;
            transform: none !important;
            margin: 0 !important;
        }
    }
</style>
