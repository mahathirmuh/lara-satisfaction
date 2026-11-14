<link href="{{ asset('assets/css/apps.min.css') }}?v={{ config('merdeka.app_version') }}" rel="stylesheet" />
<link href="{{ asset('assets/libs/tabler-icons/tabler-icons.min.css') }}?v=2.36.0" rel="stylesheet" />
<link href="{{ asset('assets/css/fonts/fonts.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/custom.css') }}?v={{ config('merdeka.app_version') }}" rel="stylesheet" />
<style>
    body {
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

    .btn-login {
        color: #000;
        background-color: #fff;
        border-color: #4299e1;
    }

    .btn-login:hover {
        color: #000;
        background-color: #ecf5fc;
        border-color: #4299e1;
    }

    .auth-card {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.55);
        border-radius: 20px;
        overflow: hidden;
    }

    .auth-carousel,
    .auth-carousel .carousel-inner,
    .auth-carousel .carousel-item {
        height: 100%;
        min-height: 560px;
    }

    .auth-carousel .carousel-item img {
        object-fit: cover;
        height: 100%;
        min-height: 560px;
    }

    @media (min-width: 768px) {
        .gradient-form {
            min-height: 100vh;
        }
    }
</style>
@stack('style')
