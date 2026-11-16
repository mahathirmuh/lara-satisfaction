<link href="{{ asset('assets/css/apps.min.css') }}?v={{ config('merdeka.app_version') }}" rel="stylesheet" />
<link href="{{ asset('assets/libs/tabler-icons/tabler-icons.min.css') }}?v=2.36.0" rel="stylesheet" />
<link href="{{ asset('assets/css/fonts/fonts.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/custom.css') }}?v={{ config('merdeka.app_version') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/merdeka-ui.css') }}?v={{ @filemtime(public_path('assets/css/merdeka-ui.css')) }}" rel="stylesheet" />
<style>
    /* The photo behind the sign-in, darkened; the colour under it while it loads. */
    body {
        background-color: var(--rm-gray-900);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        font-size: 13px;
    }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        background-image: url({{ asset('assets/img/branding/mdka-primary-bg.webp') }});
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;
        filter: brightness(50%);
        z-index: -1;
    }
</style>
@stack('style')
