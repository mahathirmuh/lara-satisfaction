@php($lastSection = null)
@foreach ($rootMenus as $menu)
    @if ($menu->section && $menu->section !== $lastSection && $menu->isVisibleFor($allowedMenuIds))
        <li class="rm-nav-section" role="presentation"><span>{{ __($menu->section) }}</span></li>
        @php($lastSection = $menu->section)
    @endif
    @include('main.sidebar.menu-item', ['menu' => $menu, 'allowedMenuIds' => $allowedMenuIds])
@endforeach
