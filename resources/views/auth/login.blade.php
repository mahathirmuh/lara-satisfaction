@extends('auth.main.app')

@section('page-title', __('Sign In'))

@push('style')
    <style>
        /* The sign-in of MTI Radio Management (D:\Apps\Laravel\radio-management, auth.login), as its production has it:
           a card on the darkened photo (the photo is the body's, auth.components.style), the photos at the left (7/12),
           the sign-in at the right (5/12). */
        .rm-login {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 16px;
        }

        .rm-login-card {
            display: grid;
            grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
            width: 1070.66px;
            max-width: 100%;
            min-height: 689.98px;
            overflow: hidden;
            border: 1px solid var(--tblr-border-color-translucent, rgba(4, 32, 69, .14));
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .55);
        }

        @media (max-width: 1399.98px) {
            .rm-login-card {
                width: 920.66px;
            }
        }

        @media (max-width: 1199.98px) {
            .rm-login-card {
                width: 928px;
            }
        }

        /* Four photos that fade one into the next (24 s for all four). */
        .rm-login-photos {
            position: relative;
            min-height: 687.98px;
            overflow: hidden;
            background: var(--rm-gray-200);
        }

        .rm-login-photos img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            animation: rm-xfade 24s linear infinite;
        }

        .rm-login [data-xf="0"] {
            animation-delay: -0.72s;
        }

        .rm-login [data-xf="1"] {
            animation-delay: 6s;
        }

        .rm-login [data-xf="2"] {
            animation-delay: 12s;
        }

        .rm-login [data-xf="3"] {
            animation-delay: 18s;
        }

        @media (prefers-reduced-motion: reduce) {
            .rm-login [data-xf] {
                animation: none !important;
                opacity: 0 !important;
            }

            .rm-login [data-xf="0"] {
                opacity: 1 !important;
            }
        }

        /* The sign-in side, from the top: the logos, the title, Microsoft, the account form, the guide; the footer at the
           foot. The font is the system one, Tabler's own stack. */
        .rm-login-side {
            display: flex;
            flex-direction: column;
            padding: 32px 48px 8px;
            font-family: -apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
        }

        .rm-login-side .form-control,
        .rm-login-side .btn-rm {
            font-family: inherit;
        }

        .rm-login-logos {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            height: 56px;
        }

        /* Merdeka Tsingshan Indonesia in the middle, between the two group logos: 28 high where the panel is wide, 24 where
           it is narrower, so that the three keep some room between them. */
        .rm-login-logos .rm-login-logo-mti {
            display: block;
            height: 24px;
            width: auto;
            min-width: 0;
            object-fit: contain;
        }

        @media (min-width: 1400px) {
            .rm-login-logos .rm-login-logo-mti {
                height: 28px;
            }
        }

        .rm-login-body {
            display: flex;
            flex-direction: column;
            margin-top: 56px;
        }

        .rm-login-title {
            margin: 0;
            color: var(--tblr-secondary);
            font-size: 24px;
            line-height: 32px;
            font-weight: 600;
            text-align: center;
        }

        .rm-login-rule {
            height: 1px;
            margin-top: 16px;
            background: var(--rm-gray-200);
        }

        .rm-login-hint {
            margin-top: 16px;
            color: var(--tblr-secondary);
            font-size: 15px;
            line-height: 21.43px;
            text-align: center;
        }

        /* The yellow badge beside a way in that is not set up yet. */
        .rm-login-hint .rm-badge,
        .rm-ms-button .rm-badge {
            margin-left: 4px;
            vertical-align: middle;
        }

        .rm-login-sso {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 16px;
        }

        .rm-ms-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
            height: 58px;
            border-radius: 8px;
            background: #fff;
            box-shadow: inset 0 0 0 1px var(--rm-gray-300);
            color: var(--rm-gray-900);
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color .12s ease-out, box-shadow .12s ease-out;
        }

        .rm-ms-button.is-pending {
            padding: 0 12px;
        }

        .rm-ms-button:hover {
            background: #ecf5fc;
            box-shadow: inset 0 0 0 1px #4299e1;
            color: var(--rm-gray-900);
            text-decoration: none;
        }

        .rm-ms-button:active {
            background: #dbeafe;
        }

        /* The four squares of Microsoft (its colours, not tokens of the app). */
        .rm-ms-mark {
            display: inline-grid;
            grid-template-columns: 10px 10px;
            gap: 2px;
        }

        .rm-ms-mark > span {
            height: 10px;
        }

        .rm-ms-mark.lg {
            grid-template-columns: 16px 16px;
            gap: 3px;
        }

        .rm-ms-mark.lg > span {
            height: 16px;
        }

        .rm-ms-mark > span:nth-child(1) {
            background: #f25022;
        }

        .rm-ms-mark > span:nth-child(2) {
            background: #7fba00;
        }

        .rm-ms-mark > span:nth-child(3) {
            background: #00a4ef;
        }

        .rm-ms-mark > span:nth-child(4) {
            background: #ffb900;
        }

        .rm-login-or {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16.5px;
            color: var(--tblr-secondary);
            font-size: 10px;
            line-height: 16px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .rm-login-or::before,
        .rm-login-or::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--rm-gray-200);
        }

        .rm-login-or + .rm-login-hint {
            margin-top: 16.5px;
        }

        /* The account form, 16px narrower than the rest at either side. */
        #loginForm {
            margin-top: 16px;
            padding: 0 16px;
        }

        #loginForm fieldset {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        #loginForm .form-label {
            margin-bottom: 8px;
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
        }

        #loginForm .form-control {
            height: 36px;
            font-size: 14px;
        }

        .rm-login-submit {
            width: 100%;
            height: 36px;
            font-size: 14px;
        }

        .rm-login-guide {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-top: 36px;
            color: var(--rm-gray-700);
            font-size: 14px;
            line-height: 24px;
        }

        .rm-login-guide button {
            padding: 3px 4px;
            border: 0;
            border-radius: 4px;
            background: var(--rm-sky-100);
            color: var(--rm-sky-700);
            font-size: 12px;
            line-height: 18px;
            font-weight: 500;
            transition: background-color .12s ease-out;
        }

        .rm-login-guide button:hover {
            background: #bae6fd;
        }

        /* At the foot of the side, 16px below the guide at the least. */
        .rm-login-foot {
            position: relative;
            margin-top: auto;
            padding-top: 16px;
            color: var(--tblr-secondary);
            font-size: 12px;
            line-height: 17.14px;
            text-align: center;
        }

        /* The language of the page, at the right end of the foot (the copyright stays in the middle). */
        .rm-login-lang {
            position: absolute;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        /* Under the copyright where the three of them do not fit beside it: the narrower card (992 to 1399 wide) and a
           phone. */
        @media (min-width: 992px) and (max-width: 1399.98px), (max-width: 575.98px) {
            .rm-login-lang {
                position: static;
                justify-content: center;
                margin-top: 6px;
            }
        }

        .rm-login-lang button {
            padding: 0 4px;
            border: 0;
            border-radius: 4px;
            background: transparent;
            color: var(--rm-gray-400);
            font-size: 12px;
            line-height: 17.14px;
            font-weight: 600;
            cursor: pointer;
            transition: color .12s ease-out;
        }

        .rm-login-lang button:hover {
            color: var(--rm-gray-700);
        }

        .rm-login-lang button[aria-pressed="true"] {
            color: var(--rm-gray-900);
            cursor: default;
        }

        .rm-login-lang button:focus-visible {
            outline: 2px solid var(--rm-brand-600);
            outline-offset: 1px;
        }

        .rm-login-lang span {
            color: var(--rm-gray-300);
        }

        /* The guide: 440 wide, a cross at the top right, the logos and the words in a ringed box. */
        #accessLoginGuide .modal-dialog {
            max-width: 440px;
        }

        #accessLoginGuide .modal-content {
            position: relative;
            padding: 20px;
            border: 0;
            border-radius: 12px;
            box-shadow: var(--rm-shadow-xl);
        }

        #accessLoginGuide .rm-dialog-close {
            position: absolute;
            top: 12px;
            right: 12px;
        }

        .rm-guide-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }

        .rm-guide-box {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 40px 24px;
            border-radius: 8px;
            box-shadow: inset 0 0 0 1px var(--rm-gray-200);
            color: var(--rm-gray-900);
            font-size: 15px;
            line-height: 24px;
            text-align: center;
        }

        .rm-guide-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
        }

        .rm-guide-domain {
            color: var(--rm-warning-700);
            font-size: 13px;
        }

        .rm-guide-sso {
            color: var(--rm-sky-700);
            font-weight: 700;
        }

        .rm-guide-logos > .rm-guide-pipe {
            color: var(--rm-gray-400);
            font-size: 32px;
            line-height: 1;
            font-weight: 300;
        }

        @media (max-width: 991.98px) {
            .rm-login {
                padding: 16px;
            }

            .rm-login-card {
                grid-template-columns: minmax(0, 1fr);
                min-height: 0;
            }

            .rm-login-photos {
                display: none;
            }

            .rm-login-side {
                padding: 28px 20px 16px;
            }
        }
    </style>
@endpush

@section('content')
    <section class="rm-login">
        <div class="rm-login-card">
            <div class="rm-login-photos" aria-hidden="true">
                <img data-xf="0" src="{{ asset('assets/img/bg-auth/mdka-bg.jpg') }}" alt="">
                <img data-xf="1" src="{{ asset('assets/img/bg-auth/mdka-bg-1.jpg') }}" alt="">
                <img data-xf="2" src="{{ asset('assets/img/bg-auth/mdka-bg-2.jpg') }}" alt="">
                <img data-xf="3" src="{{ asset('assets/img/bg-auth/mdka-bg-3.jpg') }}" alt="">
            </div>

            <div class="rm-login-side">
                {{-- Merdeka Copper Gold 38 high, the square Merdeka Battery Materials 56; Merdeka Tsingshan Indonesia between them. --}}
                <div class="rm-login-logos">
                    <img src="{{ asset('assets/img/branding/mcg-logo.png') }}" height="38" alt="Merdeka Copper Gold">
                    <img src="{{ asset('assets/img/branding/mti-corporate.svg') }}" class="rm-login-logo-mti" height="24"
                        alt="Merdeka Tsingshan Indonesia">
                    <img src="{{ asset('assets/img/branding/mbm-logo.jpg') }}" height="56" alt="Merdeka Battery Materials">
                </div>

                <div class="rm-login-body">
                    <h1 class="rm-login-title">{{ config('merdeka.app_name') }}</h1>
                    <div class="rm-login-rule"></div>

                    @if ($ssoProviders !== [])
                        <div class="rm-login-hint">{{ __('Sign in with SSO') }}</div>
                        <div class="rm-login-sso">
                            @foreach ($ssoProviders as $provider => $isConfigured)
                                <a href="{{ route('sso.redirect', $provider) }}" id="sso-{{ $provider }}"
                                    @class(['rm-ms-button', 'is-pending' => ! $isConfigured])>
                                    <img src="{{ asset('assets/img/logo/'.$provider.'.png') }}" width="24" height="24" alt="">
                                    {{ $provider === 'microsoft' ? 'Microsoft' : 'Google' }}
                                    @unless ($isConfigured)
                                        <span class="rm-badge yellow">{{ __('Coming soon') }}</span>
                                    @endunless
                                </a>
                            @endforeach
                        </div>
                        <div class="rm-login-or">{{ __('Or') }}</div>
                    @endif

                    <div class="rm-login-hint">
                        {{ __('Sign in with Merdeka Account') }}
                        @unless ($isNetworkSignInConfigured)
                            <span class="rm-badge yellow">{{ __('Coming soon') }}</span>
                        @endunless
                    </div>

                    {{-- The Merdeka account is the network (Active Directory) one: its username, or the company e-mail, whose
                         "@domain" part LoginRequest drops. --}}
                    <form id="loginForm" method="POST" action="{{ route('login.store') }}" novalidate>
                        @csrf
                        <fieldset @disabled(! $isNetworkSignInConfigured)>
                            <div class="rm-field">
                                <label for="username" class="form-label">{{ __('Email or username') }}</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                                    name="username" value="{{ old('username') }}" placeholder="{{ __('Email or username') }}"
                                    autocomplete="username" autocapitalize="none" spellcheck="false" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="rm-field">
                                <label for="password" class="form-label">{{ __('Password') }}</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                                    name="password" placeholder="{{ __('Password') }}" autocomplete="current-password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn-rm primary rm-login-submit" id="loginSubmit">{{ __('Login') }}</button>
                        </fieldset>
                    </form>

                    <div class="rm-login-guide">
                        {{ __('Access Login Guide:') }}
                        <button type="button" data-bs-toggle="modal" data-bs-target="#accessLoginGuide">{{ __('Click here') }}</button>
                    </div>
                </div>

                <div class="rm-login-foot">
                    &copy; {{ config('merdeka.company_name') }} {{ date('Y') }}
                    {{-- The language of the page: EN | ID | 中文. --}}
                    <form method="POST" action="{{ route('locale.update') }}" class="rm-login-lang" aria-label="{{ __('Language') }}">
                        @csrf
                        @foreach (config('app.available_locales') as $locale => $language)
                            @unless ($loop->first)
                                <span aria-hidden="true">|</span>
                            @endunless
                            <button type="submit" name="locale" value="{{ $locale }}" lang="{{ str_replace('_', '-', $locale) }}"
                                title="{{ $language }}" aria-label="{{ $language }}"
                                aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">{{ $locale === 'zh_CN' ? '中文' : strtoupper($locale) }}</button>
                        @endforeach
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- How to sign in with Microsoft, and with Google when it is offered. --}}
    <div class="modal fade" id="accessLoginGuide" tabindex="-1" aria-labelledby="accessLoginGuideTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="rm-dialog-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"><i
                        class="ti ti-x"></i></button>
                <h2 class="visually-hidden" id="accessLoginGuideTitle">{{ __('Access Login Guide') }}</h2>
                <div class="rm-guide-list">
                    <div class="rm-guide-box">
                        <div class="rm-guide-logos">
                            <img src="{{ asset('assets/img/branding/mbm-logo.png') }}" height="42" alt="Merdeka Battery Materials">
                            <span class="rm-guide-pipe" aria-hidden="true">|</span>
                            <span class="rm-ms-mark lg" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
                        </div>
                        <div>
                            Merdeka Battery Materials Group
                            <br><span class="rm-guide-domain">@merdekabattery.com</span>
                            <br>{{ __('access via') }}
                            <br><span class="rm-guide-sso">Microsoft SSO</span>
                            @unless ($ssoProviders['microsoft'] ?? false)
                                <br><span class="rm-badge yellow">{{ __('Coming soon') }}</span>
                            @endunless
                        </div>
                    </div>
                    @isset($ssoProviders['google'])
                        <div class="rm-guide-box">
                            <div class="rm-guide-logos">
                                <img src="{{ asset('assets/img/branding/mcg-logo.png') }}" height="42" alt="Merdeka Copper Gold">
                                <span class="rm-guide-pipe" aria-hidden="true">|</span>
                                <img src="{{ asset('assets/img/logo/google.png') }}" height="32" alt="Google">
                            </div>
                            <div>
                                Merdeka Copper Gold Group
                                <br><span class="rm-guide-domain">@merdekacoppergold.com</span>
                                <br>{{ __('access via') }}
                                <br><span class="rm-guide-sso">Google SSO</span>
                            </div>
                        </div>
                    @endisset
                </div>
            </div>
        </div>
    </div>
@endsection

@push('javascript')
    <script>
        // While the sign-in is sent: a spinner in the button, and no second click.
        $(function() {
            $('#loginForm').on('submit', function() {
                $('#loginSubmit').addClass('is-busy').prop('disabled', true).attr('aria-busy', 'true').text(@json(__('Signing in…')));
            });
        });
    </script>
@endpush
