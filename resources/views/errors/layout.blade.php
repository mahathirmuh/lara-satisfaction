<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('code') - @yield('title') | {{ config('merdeka.app_name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/branding/favicon.ico') }}" />
    <link href="{{ asset('assets/css/apps.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/libs/tabler-icons/tabler-icons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/fonts/fonts.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet" />
</head>

<body class="d-flex flex-column mdka-bg-gray-50">
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                <img src="{{ asset('assets/img/branding/mcg-logo.png') }}" height="36" alt="Merdeka Copper Gold">
                <img src="{{ asset('assets/img/branding/mbm-logo.png') }}" height="36" alt="Merdeka Battery Materials">
            </div>
            <div class="card card-md">
                <div class="card-body">
                    <div class="empty p-0">
                        <div class="empty-header mdka-text-brand-500">@yield('code')</div>
                        <p class="empty-title">@yield('title')</p>
                        <p class="empty-subtitle text-secondary">@yield('message')</p>
                        <div class="empty-action">
                            <a href="{{ url('/') }}" class="btn btn-primary">
                                <i class="ti ti-arrow-left me-1"></i> Take me home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center text-secondary mt-3 small">
                &copy; {{ date('Y') }} {{ config('merdeka.company_name') }}
            </div>
        </div>
    </div>
</body>

</html>
