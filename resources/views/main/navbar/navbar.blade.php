<!-- Navbar -->
<header class="navbar navbar-expand-md bg-white d-none d-lg-flex d-print-none sticky-top border-bottom">
    <div class="container-xl">
        <div class="d-flex align-items-center gap-2">
            <button id="sidebarControl" type="button" class="btn bg-secondary-lt p-1 d-none d-md-flex align-items-center"
                aria-label="{{ __('Toggle sidebar') }}">
                <i class="ti ti-layout-sidebar text-dark h2 mb-0"></i>
            </button>
            <div class="fw-bolder mdka-text-brand-500 h3 mb-0">
                @yield('page-title')
            </div>
        </div>
        <div class="navbar-nav flex-row order-md-last align-items-center">
            @if (Auth::user()->company)
                <p class="mb-0 text-primary me-3" style="font-size: 13px">
                    <strong>{{ Auth::user()->company->name }}</strong>
                </p>
            @endif

            <x-global.locale-switcher class="me-3" />

            <div class="me-3">
                @include('main.navbar.notifications')
            </div>

            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                    aria-label="{{ __('Open user menu') }}">
                    <span class="avatar avatar-sm rounded-circle position-relative"
                        style="background-image: url({{ asset('assets/img/avatar/1.png') }})">
                        <span class="status-dot status-dot-animated bg-green position-absolute bottom-0 end-0 d-block"></span>
                    </span>
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ Auth::user()->name }}</div>
                        <div class="mt-1 small text-muted">{{ Auth::user()->email }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('logout') }}" class="dropdown-item text-danger" onclick="logout(event)">
                        <i class="ti ti-logout me-2"></i> {{ __('Logout') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
