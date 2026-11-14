@php($assetVersion = config('merdeka.app_version'))
<link href="{{ asset('assets/css/apps.min.css') }}?v={{ $assetVersion }}" rel="stylesheet" />
<link href="{{ asset('assets/libs/tabler-icons/tabler-icons.min.css') }}?v=2.36.0" rel="stylesheet" />
<link href="{{ asset('assets/css/fonts/fonts.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/custom.css') }}?v={{ $assetVersion }}" rel="stylesheet" />
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

    .dropdown-menu-columns {
        background-color: rgb(239, 244, 251);
        color: #000;
    }

    .navbar-vertical {
        overflow: auto !important;
    }

    .navbar-expand-lg {
        transition: width 0.2s ease-in-out;
    }

    .page-wrapper,
    .navbar-expand-md {
        transition: margin-left 0.2s ease-in-out;
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

    /* Notification bell */
    .notification-card {
        width: 360px;
        max-width: calc(100vw - 2rem);
    }

    .notification-list {
        max-height: 360px;
        overflow-y: auto;
    }

    .notification-bell .badge-notification {
        position: absolute;
        top: -.25rem;
        right: -.6rem;
        font-size: .65rem;
        padding: .15rem .35rem;
    }

    /* Mobile header: the sidebar clips overflowing content, so pin the list to the viewport */
    @media (max-width: 991.98px) {
        aside .notification-bell .dropdown-menu {
            position: fixed !important;
            inset: 4.25rem .75rem auto .75rem !important;
            transform: none !important;
            margin: 0 !important;
        }

        aside .notification-bell .dropdown-menu-arrow::before,
        aside .notification-bell .dropdown-menu-arrow::after {
            display: none;
        }

        aside .notification-bell .notification-card {
            width: 100%;
            max-width: none;
        }
    }
</style>
