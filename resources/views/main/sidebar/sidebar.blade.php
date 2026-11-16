<!-- Sidebar -->
<aside class="navbar navbar-vertical navbar-expand-lg mdka-bg-gray-50" data-bs-theme="light">
    <div class="container-fluid overflow-x-hidden">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('dashboard.index') }}" class="d-flex flex-column gap-2 align-items-center link-underline link-underline-opacity-0">
                <img src="{{ asset('assets/img/branding/mcg-logo.png') }}" height="32" alt="Merdeka Copper Gold"
                    class="navbar-brand-image">
                <img src="{{ asset('assets/img/branding/mbm-logo.png') }}" height="32" alt="Merdeka Battery Materials"
                    class="navbar-brand-image">

                <img src="{{ asset('assets/img/branding/favicon.ico') }}" height="20" alt="Merdeka"
                    class="navbar-brand-image-small d-none">
                <img src="{{ asset('assets/img/branding/mbm-logo-small.png') }}" height="24" alt="Merdeka"
                    class="navbar-brand-image-small d-none">
            </a>
        </h1>

        <div class="navbar-nav flex-row d-lg-none align-items-center gap-3">
            <x-global.locale-switcher />
            @include('main.navbar.notifications')
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                    aria-label="{{ __('Open user menu') }}">
                    <span class="avatar avatar-sm rounded-circle"
                        style="background-image: url({{ asset('assets/img/avatar/1.png') }})"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <span class="dropdown-header">{{ Auth::user()->name }}</span>
                    <a href="{{ route('profile.show') }}" class="dropdown-item">
                        <i class="ti ti-user-circle me-2"></i> {{ __('My Profile') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('logout') }}" class="dropdown-item text-danger" onclick="logout(event)">
                        <i class="ti ti-logout me-2"></i> {{ __('Logout') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav py-lg-3">
                @include('main.sidebar.menu')
            </ul>
        </div>
        <img class="sidebar-background" src="{{ asset('assets/img/branding/sidebar-background.png') }}" alt=""
            style="user-select: none; pointer-events: none;">
    </div>
</aside>

@push('javascript')
    <script>
        $(function() {
            if (localStorage.getItem('sidebarState') === 'collapsed') {
                collapseSidebar();
            } else {
                expandSidebar();
            }

            $('#sidebarControl').on('click', function() {
                if ($('.navbar-expand-lg').hasClass('collapsed')) {
                    expandSidebar();
                    localStorage.setItem('sidebarState', 'expanded');
                } else {
                    collapseSidebar();
                    localStorage.setItem('sidebarState', 'collapsed');
                }
            });
        });

        function expandSidebar() {
            const navbar = $('.navbar-expand-lg');

            navbar.removeClass('collapsed').css({
                width: '',
                'overflow-y': 'auto'
            });

            $('.navbar-nav').css('gap', '');
            $('.sidebar-control').removeClass('d-none');
            $('.navbar-brand-image').removeClass('d-none');
            $('.navbar-brand-image-small').addClass('d-none');
            $('.sidebar-item').css('padding-left', 'calc(calc(calc(var(--tblr-page-padding) * 2)/ 2) + 1.75rem)');
            $('.nav-link-icon').css({
                'padding-left': '0',
                transform: 'scale(1)'
            });

            if (window.innerWidth >= 992) {
                $('.navbar-expand-md').css('margin-left', '15rem');
                $('.page-wrapper').css('margin-left', '15rem');
            }
        }

        function collapseSidebar() {
            const navbar = $('.navbar-expand-lg');

            navbar.addClass('collapsed').css({
                width: '80px',
                'overflow-y': 'auto'
            });

            $('.navbar-nav').css('gap', '0.5rem');
            $('.sidebar-control').addClass('d-none');
            $('.navbar-brand-image').addClass('d-none');
            $('.navbar-brand-image-small').removeClass('d-none');
            $('.sidebar-item').css('padding-left', '15px');
            $('.nav-link-icon').css({
                'padding-left': '10px',
                transform: 'scale(1.2)'
            });

            if (window.innerWidth >= 992) {
                $('.navbar-expand-md').css('margin-left', '80px');
                $('.page-wrapper').css('margin-left', '80px');
            }
        }
    </script>
@endpush
