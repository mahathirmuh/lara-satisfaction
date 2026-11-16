@extends('auth.main.app')

@section('page-title', 'Sign In')

@section('content')
    @php
        $carouselImages = ['mdka-bg.jpg', 'mdka-bg-1.jpg', 'mdka-bg-2.jpg', 'mdka-bg-3.jpg'];
    @endphp
    <section class="gradient-form d-flex align-items-center">
        <div class="container py-5">
            <div class="row d-flex justify-content-center align-items-center">
                <div class="col-xl-10">
                    <div class="card auth-card rounded-4 text-black border-0">
                        <div class="row g-0">
                            <div class="col-lg-7 d-none d-lg-flex align-items-stretch">
                                <div id="authCarousel" class="carousel slide carousel-fade auth-carousel w-100"
                                    data-bs-ride="carousel">
                                    <div class="carousel-indicators carousel-indicators-thumb">
                                        @foreach ($carouselImages as $index => $image)
                                            <button type="button" data-bs-target="#authCarousel"
                                                data-bs-slide-to="{{ $index }}"
                                                class="ratio ratio-4x3 {{ $index === 0 ? 'active' : '' }}"
                                                aria-label="Slide {{ $index + 1 }}"
                                                style="background-image: url({{ asset('assets/img/bg-auth/' . $image) }})"></button>
                                        @endforeach
                                    </div>
                                    <div class="carousel-inner">
                                        @foreach ($carouselImages as $index => $image)
                                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                                <img class="d-block w-100" alt=""
                                                    src="{{ asset('assets/img/bg-auth/' . $image) }}">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 d-flex flex-column justify-content-between bg-white">
                                <div class="p-4 p-md-5 pb-0 pb-md-0 mx-md-3 d-flex justify-content-between align-items-center">
                                    <img src="{{ asset('assets/img/branding/mcg-logo.png') }}" height="38"
                                        alt="Merdeka Copper Gold">
                                    <img src="{{ asset('assets/img/branding/mbm-logo.jpg') }}" height="56"
                                        alt="Merdeka Battery Materials">
                                </div>
                                <div class="card-body px-4 px-md-5 pt-0 mx-md-3">
                                    <div class="text-center mt-4">
                                        <h1 class="text-secondary mb-1">{{ config('merdeka.app_name') }}</h1>
                                        <div class="text-muted">Authentication required via employee credentials.</div>
                                    </div>
                                    <hr class="my-3">

                                    @if ($ssoProviders !== [])
                                        <div class="text-center mb-3">
                                            <span class="text-muted" style="font-size: 15px">Sign In with SSO</span>
                                        </div>
                                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mb-2">
                                            @foreach ($ssoProviders as $provider => $isConfigured)
                                                <a href="{{ route('sso.redirect', $provider) }}" id="sso-{{ $provider }}"
                                                    class="btn btn-login btn-lg p-3 d-flex flex-row justify-content-center align-items-center gap-2 flex-fill">
                                                    <img src="{{ asset('assets/img/logo/' . $provider . '.png') }}" width="24"
                                                        alt="">
                                                    <span class="h4 mb-0">{{ $provider === 'microsoft' ? 'Microsoft' : 'Google' }}</span>
                                                    @unless ($isConfigured)
                                                        <span class="badge bg-yellow-lt ms-1" style="font-size: .7rem">Coming soon</span>
                                                    @endunless
                                                </a>
                                            @endforeach
                                        </div>
                                        <div class="hr-text my-4"><span class="h6 mb-0">Or</span></div>
                                    @endif

                                    <div class="text-center">
                                        <span class="text-muted" style="font-size: 15px">Sign in with Network Account (LDAP)</span>
                                        @unless ($isNetworkSignInConfigured)
                                            <span class="badge bg-yellow-lt ms-1" style="font-size: .7rem">Coming soon</span>
                                        @endunless
                                    </div>
                                    <form id="loginForm" class="p-3 rounded" method="POST"
                                        action="{{ route('login.store') }}" novalidate>
                                        @csrf
                                        <fieldset @disabled(! $isNetworkSignInConfigured)>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="username" class="form-label required">Username</label>
                                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                                    id="username" name="username" value="{{ old('username') }}"
                                                    placeholder="firstname.lastname" autocomplete="username" required
                                                    autofocus>
                                                @error('username')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-hint">Your network (Windows) username and password.</small>
                                            </div>
                                            <div class="col-12">
                                                <label for="password" class="form-label required">Password</label>
                                                <div class="input-group input-group-flat">
                                                    <input type="password"
                                                        class="form-control @error('password') is-invalid @enderror"
                                                        id="password" name="password" placeholder="Password"
                                                        autocomplete="current-password" required>
                                                    <span class="input-group-text">
                                                        <a href="#" class="link-secondary" id="togglePassword"
                                                            title="Show password" data-bs-toggle="tooltip">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </span>
                                                </div>
                                                @error('password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12">
                                                <label class="form-check mb-0">
                                                    <input type="checkbox" class="form-check-input" name="remember"
                                                        value="1" @checked(old('remember'))>
                                                    <span class="form-check-label">Remember me on this device</span>
                                                </label>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="ti ti-login me-1"></i> Login
                                                </button>
                                            </div>
                                        </div>
                                        </fieldset>
                                    </form>
                                    <div class="text-center">
                                        Access Login Guide :
                                        <button type="button" class="btn btn-sm btn-info rounded" data-bs-toggle="modal"
                                            data-bs-target="#accessLoginGuide">
                                            Click Here
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex flex-column align-items-center pb-3 gap-1">
                                    <a href="{{ route('landing') }}" class="small">
                                        <i class="ti ti-arrow-left"></i> Back to survey overview
                                    </a>
                                    <small class="text-muted">&copy; {{ config('merdeka.company_name') }}
                                        {{ date('Y') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Access Login Guide -->
    <div class="modal fade" id="accessLoginGuide" tabindex="-1" aria-labelledby="accessLoginGuideLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accessLoginGuideLabel">Access Login Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    @php($guideColumn = isset($ssoProviders['google']) ? 'col-md-4' : 'col-md-6')
                    <div class="row row-deck g-3">
                        @isset($ssoProviders['google'])
                            <div class="{{ $guideColumn }}">
                                <div class="px-3 py-5 w-100 border rounded d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-center align-items-center gap-2">
                                        <img src="{{ asset('assets/img/branding/mcg-logo.png') }}" height="42" alt="">
                                        <span class="display-5 text-secondary fw-light">|</span>
                                        <img src="{{ asset('assets/img/logo/google.png') }}" height="36" alt="">
                                    </div>
                                    <div class="text-center">
                                        Merdeka Copper Gold Group
                                        <br><span class="text-warning small">@merdekacoppergold.com</span>
                                        <br>access via
                                        <br><span class="text-primary fw-bold">Google SSO</span>
                                    </div>
                                </div>
                            </div>
                        @endisset
                        <div class="{{ $guideColumn }}">
                            <div class="px-3 py-5 w-100 border rounded d-flex flex-column gap-3">
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <img src="{{ asset('assets/img/branding/mbm-logo.png') }}" height="42" alt="">
                                    <span class="display-5 text-secondary fw-light">|</span>
                                    <img src="{{ asset('assets/img/logo/microsoft.png') }}" height="36" alt="">
                                </div>
                                <div class="text-center">
                                    Merdeka Battery Materials Group
                                    <br><span class="text-warning small">@merdekabattery.com, @scmnickel.com</span>
                                    <br>access via
                                    <br><span class="text-azure fw-bold">Microsoft SSO</span>
                                    @unless ($ssoProviders['microsoft'] ?? false)
                                        <br><span class="badge bg-yellow-lt mt-1">Coming soon</span>
                                    @endunless
                                </div>
                            </div>
                        </div>
                        <div class="{{ $guideColumn }}">
                            <div class="px-3 py-5 w-100 border rounded d-flex flex-column gap-3">
                                <h3 class="text-center mb-0">Login by Network Account (LDAP)</h3>
                                <div class="text-center">
                                    Sign in with the username and password of your company network (Windows)
                                    account. This application has no passwords of its own: please contact the IT
                                    Helpdesk if you do not have a network or Microsoft account.
                                    @unless ($isNetworkSignInConfigured)
                                        <br><span class="badge bg-yellow-lt mt-1">Coming soon</span>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('javascript')
    <script>
        $(function() {
            $('#togglePassword').on('click', function(event) {
                event.preventDefault();
                const input = $('#password');
                const isHidden = input.attr('type') === 'password';

                input.attr('type', isHidden ? 'text' : 'password');
                $(this).find('i').toggleClass('ti-eye', !isHidden).toggleClass('ti-eye-off', isHidden);
            });
        });
    </script>
@endpush
