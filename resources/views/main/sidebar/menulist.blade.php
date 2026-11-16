@php($lastSection = null)
@foreach ($rootMenus as $menu)
    @if ($menu->isVisibleFor($allowedMenuIds))
        @if ($menu->section && $menu->section !== $lastSection)
            <li class="nav-item menu-section-header">
                <span class="subheader text-wrap sidebar-control">{{ __($menu->section) }}</span>
            </li>
            @php($lastSection = $menu->section)
        @endif
        @include('main.sidebar.menu-item', ['menu' => $menu, 'allowedMenuIds' => $allowedMenuIds])
    @endif
@endforeach
