<!-- Top bar -->
@php
    $me = Auth::user();
    $myRoles = $me->roles->pluck('name')->filter()->implode(', ');
@endphp
<header class="navbar navbar-expand-md d-none d-lg-flex d-print-none sticky-top rm-topbar">
    <div class="rm-topbar-inner">
        <button id="sidebarControl" type="button" class="rm-icon-btn is-ringed" aria-controls="sidebar-menu"
            aria-pressed="false" aria-label="{{ __('Expand or collapse the menu') }}" title="{{ __('Expand or collapse the menu') }}">
            <i class="ti ti-layout-sidebar" aria-hidden="true"></i>
        </button>
        {{-- The heading of the page (main.index); the path to it is under the bar. --}}
        <h1 class="rm-topbar-title">{!! $heading !!}</h1>
        <div class="rm-topbar-end">
            <span class="rm-topbar-company">{{ $me->company?->name }}</span>
            {{-- The language of the screens, as on the sign-in page: EN | ID | 中文, the one in use marked. --}}
            <form method="POST" action="{{ route('locale.update') }}" class="rm-seg rm-topbar-lang" id="languageSwitch"
                aria-label="{{ __('Language') }}">
                @csrf
                @foreach (config('app.available_locales') as $locale => $language)
                    <button type="submit" name="locale" value="{{ $locale }}" lang="{{ str_replace('_', '-', $locale) }}"
                        title="{{ $language }}" aria-label="{{ $language }}"
                        aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                        @class(['is-on' => app()->getLocale() === $locale])>{{ $locale === 'zh_CN' ? '中文' : strtoupper($locale) }}</button>
                @endforeach
            </form>
            @include('main.navbar.notifications')
            <div class="dropdown">
                <button type="button" class="rm-user-btn" data-bs-toggle="dropdown" data-bs-offset="0,10" aria-expanded="false"
                    aria-label="{{ __('User menu') }}">
                    <img src="{{ $me->photoUrl() ?? asset('assets/img/avatar/1.png') }}" alt="" class="rm-avatar" width="32" height="32">
                    <span class="rm-user-text">
                        <span class="rm-user-name">{{ $me->name }}</span>
                        @if ($myRoles !== '')
                            <span class="rm-user-role">{{ $myRoles }}</span>
                        @endif
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end rm-menu rm-user-menu">
                    <div class="rm-menu-head">
                        <div class="rm-menu-head-name">{{ $me->name }}</div>
                        <div class="rm-menu-head-mail">{{ $me->email }}</div>
                    </div>
                    <a href="{{ route('profile.show') }}" class="dropdown-item rm-menu-item">
                        <i class="ti ti-user-circle" aria-hidden="true"></i> {{ __('My Profile') }}
                    </a>
                    <a href="{{ route('user-guide') }}" class="dropdown-item rm-menu-item" target="_blank" rel="noopener">
                        <i class="ti ti-book" aria-hidden="true"></i> {{ __('User Guide') }}
                    </a>
                    <a href="{{ route('logout') }}" class="dropdown-item rm-menu-item is-danger" onclick="logout(event)">
                        <i class="ti ti-logout" aria-hidden="true"></i> {{ __('Logout') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
