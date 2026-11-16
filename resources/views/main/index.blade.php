<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('main.components.license.license')

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', __('Dashboard')) | {{ config('merdeka.app_name') }}</title>
    <meta name="description" content="{{ config('merdeka.app_name') }} - {{ config('merdeka.company_name') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/branding/favicon.ico') }}" />
    <!--  Main Style -->
    @include('main.components.style.style')
    <meta name="theme-color" content="#ffffff">
    <script>
        // Interface language for Intl number/date formatting in page scripts, e.g. "id" or "zh-CN".
        window.appLocale = @json(str_replace('_', '-', app()->getLocale()));

        // The sidebar as the person left it (full width or the icon rail), before anything is drawn. main.sidebar.sidebar.
        try {
            if (window.localStorage.getItem('sidebarState') === 'collapsed') document.documentElement.classList.add('rm-rail');
        } catch (e) {
            // No storage (a private window): the full sidebar.
        }
    </script>
</head>

<body class="layout-fluid">
    @php
        // The heading of the page: its own (@section('heading')), else its title; in the top bar, and above the content on
        // a phone, where the top bar is hidden. (A section is kept escaped.)
        $heading = trim($__env->yieldContent('heading') ?: $__env->yieldContent('page-title'));
    @endphp
    <div class="page rm-page">
        <!--  Sidebar -->
        @include('main.sidebar.sidebar')
        <!--  Top bar -->
        @include('main.navbar.navbar')

        <div class="page-wrapper">
            <!--  Main Page -->
            <main class="page-body rm-main">
                <div class="container-xl rm-container">
                    @include('main.breadcrumb')
                    @yield('content')
                </div>
            </main>
            <!--  End OF Main Page -->
            @include('main.footer.footer')
        </div>
    </div>

    <!--  Main Javascript -->
    @include('main.components.js.js')

    <!-- Global Alert, Modal, and Dialog -->
    @include('main.modal.alert')
    @include('main.modal.modal')
</body>

</html>
