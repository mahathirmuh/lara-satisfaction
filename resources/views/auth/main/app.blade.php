<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('main.components.license.license')

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', __('Sign In')) | {{ config('merdeka.app_name') }}</title>
    <meta name="description" content="{{ config('merdeka.app_name') }} - {{ config('merdeka.company_name') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/branding/favicon.ico') }}" />
    {{-- Main Style --}}
    @include('auth.components.style.style')
    <meta name="theme-color" content="#0d6efd">
</head>

<body>
    {{-- Main Page --}}
    @yield('content')
    {{-- End OF Main Page --}}

    {{-- Main Javascript --}}
    @include('auth.components.js.js')
    @include('main.modal.alert')
</body>

</html>
