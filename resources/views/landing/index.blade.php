<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('main.components.license.license')

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('merdeka.app_name') }} | {{ config('merdeka.company_name') }}</title>
    <meta name="description" content="{{ __($content->hero_title) }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/branding/favicon.ico') }}" />
    <link href="{{ asset('assets/css/apps.min.css') }}?v={{ config('merdeka.app_version') }}" rel="stylesheet" />
    <link href="{{ asset('assets/libs/tabler-icons/tabler-icons.min.css') }}?v=2.36.0" rel="stylesheet" />
    <link href="{{ asset('assets/css/fonts/fonts.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/custom.css') }}?v={{ config('merdeka.app_version') }}" rel="stylesheet" />
    <style>
        body {
            font-size: 14px;
            background: #fff;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: 11px;
            font-weight: 700;
        }

        /* Hero: text left, image right */
        .landing-hero {
            padding: 56px 0 80px;
        }

        .landing-hero h1 {
            font-size: clamp(2.3rem, 5.2vw, 4.1rem);
            line-height: 1.03;
            letter-spacing: -0.035em;
            max-width: 13ch;
            color: var(--mdka-brand-900);
        }

        .landing-hero .lead {
            max-width: 46ch;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .hero-media {
            position: relative;
            margin: 0;
            min-height: 440px;
            height: 100%;
            border-radius: 12px;
            overflow: hidden;
            background: var(--mdka-brand-50);
            box-shadow: 0 12px 32px rgba(0, 52, 94, .08);
        }

        .hero-media img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-media::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, rgba(0, 31, 62, .08), rgba(0, 31, 62, .32));
        }

        /* Information slab */
        .landing-slab {
            background: var(--mdka-brand-50);
            padding: 44px 0;
        }

        .info-card,
        .overview-card {
            border-radius: 6px;
            min-height: 220px;
        }

        .overview-card .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--mdka-brand-200);
            flex: 0 0 8px;
        }

        /* Departments: joined tiles like the original page */
        .department-grid {
            display: grid;
            grid-template-columns: repeat(var(--department-columns, 4), minmax(0, 1fr));
            gap: 1px;
            margin-top: 12px;
            border-radius: 6px;
            overflow: hidden;
            background: var(--mdka-brand-100);
        }

        .department-tile {
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        .department-tile img,
        .department-placeholder {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
        }

        .department-placeholder {
            font-size: 3rem;
        }

        .department-tile .department-icon {
            width: 36px;
            height: 36px;
            font-size: 1.2rem;
        }

        .landing-cta {
            padding: 72px 0 80px;
        }

        @media (max-width: 991.98px) {
            .landing-hero {
                padding: 32px 0 56px;
            }

            .hero-media {
                min-height: 320px;
            }

            .department-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .department-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    {{-- Saved texts are shown as typed; the default texts (LandingContent::DEFAULTS) are translated. --}}
    @php
        $ctaUrl = auth()->check() ? route('survey-form.index') : route('login');
        $ctaLabel = auth()->check() ? __('Go to My Survey') : __('Begin Survey');
        $startLabel = $survey ? (auth()->check() ? __('Start the Survey') : __('Begin Identification')) : __('Sign In');
    @endphp

    <!-- Navbar -->
    <header class="navbar navbar-expand-md bg-white border-bottom sticky-top d-print-none">
        <div class="container-xl">
            <a href="{{ route('landing') }}" class="navbar-brand d-flex align-items-center gap-2 me-3">
                <img src="{{ asset('assets/img/branding/mcg-logo.png') }}" height="28" alt="Merdeka Copper Gold">
                <img src="{{ asset('assets/img/branding/mbm-logo.png') }}" height="28" alt="Merdeka Battery Materials">
            </a>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <a href="#guidelines" class="link-secondary d-none d-md-inline">{{ __('Guidelines') }}</a>
                <a href="#departments" class="link-secondary d-none d-md-inline">{{ __('Departments') }}</a>
                <a href="#privacy" class="link-secondary d-none d-md-inline">{{ __('Privacy') }}</a>
                <x-global.locale-switcher />
                <a href="{{ $ctaUrl }}" class="btn mdka-btn-brand-500 text-white border-0 rounded-pill px-3">
                    <i class="ti ti-clipboard-check me-1"></i> {{ $ctaLabel }}
                </a>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="landing-hero">
        <div class="container-xl">
            <div class="row g-5 align-items-stretch">
                <div class="col-lg-7 d-flex flex-column justify-content-center">
                    <div class="eyebrow mdka-text-brand-500 mb-3">{{ config('merdeka.app_name') }}</div>
                    <h1 class="fw-bold mb-4">{{ __($content->hero_title) }}</h1>
                    <p class="lead text-secondary mb-4">{{ $content->heroIntroHtml($survey) }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $ctaUrl }}" class="btn btn-lg mdka-btn-brand-500 text-white border-0 rounded-pill px-4">
                            {{ $startLabel }} <i class="ti ti-arrow-right ms-1"></i>
                        </a>
                        <a href="#guidelines" class="btn btn-lg btn-outline-secondary rounded-pill px-4">{{ __('How it works') }}</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <figure class="hero-media">
                        <img src="{{ $content->heroImageUrl() }}"
                            alt="{{ $content->hero_image_alt ? __($content->hero_image_alt) : config('merdeka.app_name') }}">
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <!-- Why your feedback matters + overview + departments -->
    <section class="landing-slab">
        <div class="container-xl">
            <div class="row g-3" id="guidelines">
                <div class="col-lg-8">
                    <div class="card info-card h-100 border-0">
                        <div class="card-body p-4 p-md-5">
                            <span class="avatar avatar-md rounded-3 mdka-bg-brand-50 mdka-text-brand-500 mb-3">
                                <i class="ti ti-shield-lock fs-2"></i>
                            </span>
                            <h2 class="mb-3">{{ __($content->feedback_title) }}</h2>
                            <p class="text-secondary mb-0" style="white-space: pre-line">{{ __($content->feedback_body) }}</p>
                            @if ($content->feedback_note)
                                <div class="eyebrow mdka-text-brand-500 mt-4">{{ __($content->feedback_note) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card overview-card h-100 mdka-bg-brand-500 text-white border-0">
                        <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center">
                            <h3 class="text-white fs-1 mb-3">{{ __('Survey Overview') }}</h3>
                            <ul class="list-unstyled d-grid gap-2 mb-0 fw-medium">
                                <li class="d-flex align-items-center gap-2">
                                    <span class="dot"></span>
                                    {{ trans_choice(':count Key Department|:count Key Departments', $departments->count()) }}
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <span class="dot"></span> {{ __(':minutes Minute Completion', ['minutes' => $survey?->estimated_minutes ?? 15]) }}
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <span class="dot"></span> {{ __('Confidential & Anonymous Processing') }}
                                </li>
                                @if ($survey)
                                    <li class="d-flex align-items-center gap-2">
                                        <span class="dot"></span> {{ __('Open :period', ['period' => $survey->periodLabel()]) }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div id="departments">
                @if ($departments->isNotEmpty())
                    @php($hasImages = $departments->contains(fn ($department): bool => filled($department->image_path)))
                    {{-- Three tiles per row when that fills every row (6, 9 departments), otherwise four. --}}
                    @php($columns = $departments->count() % 3 === 0 && $departments->count() % 4 !== 0 ? 3 : 4)
                    <div class="department-grid" style="--department-columns: {{ $columns }}">
                        @foreach ($departments as $department)
                            <article class="department-tile">
                                @if ($department->imageUrl())
                                    <img src="{{ $department->imageUrl() }}" alt="{{ $department->name }}" loading="lazy">
                                @elseif ($hasImages)
                                    <div
                                        class="department-placeholder d-flex align-items-center justify-content-center mdka-bg-{{ $department->color }}-50 mdka-text-{{ $department->color }}-600">
                                        <i class="ti ti-{{ $department->icon ?: 'building' }}"></i>
                                    </div>
                                @endif
                                <div class="p-3 d-flex align-items-start gap-3">
                                    <span
                                        class="department-icon d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0 mdka-bg-{{ $department->color }}-100 mdka-text-{{ $department->color }}-600">
                                        <i class="ti ti-{{ $department->icon ?: 'building' }}"></i>
                                    </span>
                                    <div>
                                        <h4 class="mb-1">{{ $department->name }}</h4>
                                        @if ($department->sectionsLabel() !== '')
                                            <div class="eyebrow text-secondary">{{ $department->sectionsLabel() }}</div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="card border-0 mt-3">
                        <x-global.empty-state icon="building" :title="__('No departments configured yet')" />
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="landing-cta" id="privacy">
        <div class="container-xl text-center">
            <h2 class="mb-4">{{ __($content->closing_title) }}</h2>
            <a href="{{ $ctaUrl }}" class="btn btn-lg mdka-btn-brand-500 text-white border-0 rounded-pill px-4">
                <i class="ti ti-fingerprint me-1"></i> {{ $startLabel }}
            </a>
            @if ($content->closing_note)
                <p class="text-secondary small mt-3 mb-1">{{ __($content->closing_note) }}</p>
            @endif
            <p class="text-secondary small mb-0">
                {{ __('Participation is recorded separately from your answers, so nobody can link a response back to you.') }}
            </p>
        </div>
    </section>

    <footer class="footer footer-transparent border-top d-print-none py-4">
        <div class="container-xl d-flex flex-wrap justify-content-between align-items-center gap-2 small text-secondary">
            <strong class="text-body">{{ config('merdeka.app_name') }}</strong>
            <span>&copy; {{ date('Y') }} {{ config('merdeka.company_name') }}. {{ __('Confidential & Secure.') }}</span>
        </div>
    </footer>

    <script src="{{ asset('assets/js/apps.min.js') }}?v={{ config('merdeka.app_version') }}" defer></script>
</body>

</html>
