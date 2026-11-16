@php
    $children = $menu->children;
    $hasChildren = $children->isNotEmpty();
    $menuHref = $menu->url() ?? '#';
    $isActive = $menu->route && request()->routeIs(\App\Models\UserManagement\Menu::keyForRoute($menu->route) . '.*');
    $isDescendantActive =
        $hasChildren &&
        $children->contains(
            fn($child) => $child->route &&
                request()->routeIs(\App\Models\UserManagement\Menu::keyForRoute($child->route) . '.*'),
        );
    // Top-level items live inside the sidebar <ul> and must be <li>; sub-items live inside a
    // .dropdown-menu and must be plain .dropdown-item anchors (nested <li> would break the list).
    $isRoot = is_null($menu->parent_id);
@endphp
@if ($menu->isVisibleFor($allowedMenuIds))
    @if ($hasChildren)
        @if ($isRoot)
            <li class="nav-item dropdown {{ $isDescendantActive ? 'active-mdka-nav' : '' }}">
                <a class="nav-link dropdown-toggle {{ $isDescendantActive ? 'active show' : '' }}"
                    href="#navbar-menu-{{ $menu->id }}" data-bs-toggle="dropdown" data-bs-auto-close="false"
                    role="button" aria-expanded="{{ $isDescendantActive ? 'true' : 'false' }}">
                    @if ($menu->icon)
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-{{ $menu->icon }}"></i>
                        </span>
                    @endif
                    <span class="nav-link-title sidebar-control">
                        {{ __($menu->title) }}
                    </span>
                </a>
                <div class="dropdown-menu {{ $isDescendantActive ? 'show' : '' }}">
                    <div class="dropdown-menu-columns">
                        <div class="dropdown-menu-column">
                            @foreach ($children as $child)
                                @include('main.sidebar.menu-item', ['menu' => $child, 'allowedMenuIds' => $allowedMenuIds])
                            @endforeach
                        </div>
                    </div>
                </div>
            </li>
        @else
            <span class="dropdown-header sidebar-control">{{ __($menu->title) }}</span>
            @foreach ($children as $child)
                @include('main.sidebar.menu-item', ['menu' => $child, 'allowedMenuIds' => $allowedMenuIds])
            @endforeach
        @endif
    @else
        @if ($isRoot)
            <li class="nav-item {{ $isActive ? 'active-mdka-nav' : '' }}">
                <a class="nav-link" href="{{ $menuHref }}">
                    @if ($menu->icon)
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-{{ $menu->icon }}"></i>
                        </span>
                    @endif
                    <span class="nav-link-title sidebar-control">
                        {{ __($menu->title) }}
                    </span>
                </a>
            </li>
        @else
            <a class="dropdown-item sidebar-item {{ $isActive ? 'active-mdka-nav' : '' }}" href="{{ $menuHref }}">
                @if ($menu->icon)
                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                        <i class="ti ti-{{ $menu->icon }}"></i>
                    </span>
                @endif
                <span class="nav-link-title sidebar-control">
                    {{ __($menu->title) }}
                </span>
            </a>
        @endif
    @endif
@endif
