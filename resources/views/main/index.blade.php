<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('main.components.license.license')

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Dashboard') | {{ config('merdeka.app_name') }}</title>
    <meta name="description" content="{{ config('merdeka.app_name') }} - {{ config('merdeka.company_name') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/branding/favicon.ico') }}" />
    <!--  Main Style -->
    @include('main.components.style.style')
    <meta name="theme-color" content="#0d6efd">
</head>

<body class="layout-fluid">
    <div class="page">
        <!--  Sidebar -->
        @include('main.sidebar.sidebar')
        <!--  Navbar -->
        @include('main.navbar.navbar')

        <div class="page-wrapper">
            <!--  Main Page -->
            <div class="page-body mt-1">
                <div class="container-xl" style="max-width: 1440px">
                    @yield('content')
                </div>
            </div>
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
