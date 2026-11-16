@php
    $children = $menu->children;
    $hasChildren = $children->isNotEmpty();
    // A page of a menu is any route of its menu key (the route name less its last part, as the permission middleware
    // maps it), so a survey's report keeps "Survey Report" marked.
    $isOn = fn ($item): bool => $item->url() !== null
        && request()->routeIs($item->route, \App\Models\UserManagement\Menu::keyForRoute($item->route).'.*');
    $isActive = $isOn($menu);
    $isDescendantActive = $hasChildren && $children->contains(fn ($child): bool => $isOn($child));
    $isRoot = is_null($menu->parent_id);
    $label = __($menu->title);
@endphp
{{-- A leaf menu without a route would only be a dead "#" link. --}}
@if ($menu->isVisibleFor($allowedMenuIds) && ($hasChildren || $menu->route))
    @if ($hasChildren)
        @if ($isRoot)
            @php($first = $children->first(fn ($child): bool => $child->isVisibleFor($allowedMenuIds) && $child->url() !== null))
            <li class="rm-nav-item rm-nav-group {{ $isDescendantActive ? 'is-open has-active' : '' }}" id="rm-navgroup-{{ $menu->id }}">
                <button type="button" class="rm-nav-link" data-rm-group="{{ $menu->id }}"
                    data-rm-first="{{ $first?->url() }}" data-rm-tip="{{ $label }}"
                    aria-expanded="{{ $isDescendantActive ? 'true' : 'false' }}" aria-controls="rm-navkids-{{ $menu->id }}">
                    <span class="rm-nav-icon"><i class="ti ti-{{ $menu->icon ?: 'folder' }}"></i></span>
                    <span class="rm-nav-label">{{ $label }}</span>
                    <i class="ti ti-chevron-down rm-nav-chevron" aria-hidden="true"></i>
                </button>
                <div class="rm-nav-kids" id="rm-navkids-{{ $menu->id }}">
                    <div class="rm-nav-kids-inner">
                        @foreach ($children as $child)
                            @include('main.sidebar.menu-item', ['menu' => $child, 'allowedMenuIds' => $allowedMenuIds])
                        @endforeach
                    </div>
                </div>
            </li>
        @else
            {{-- Nested sub-menu: a heading followed by its items. --}}
            <span class="rm-nav-subhead">{{ $label }}</span>
            @foreach ($children as $child)
                @include('main.sidebar.menu-item', ['menu' => $child, 'allowedMenuIds' => $allowedMenuIds])
            @endforeach
        @endif
    @else
        @if ($isRoot)
            <li class="rm-nav-item">
                <a class="rm-nav-link {{ $isActive ? 'is-active' : '' }}" href="{{ $menu->url() ?? '#' }}" data-rm-tip="{{ $label }}"
                    @if ($isActive) aria-current="page" @endif>
                    <span class="rm-nav-icon"><i class="ti ti-{{ $menu->icon ?: 'point' }}"></i></span>
                    <span class="rm-nav-label">{{ $label }}</span>
                </a>
            </li>
        @else
            <a class="rm-nav-link rm-nav-kid {{ $isActive ? 'is-active' : '' }}" href="{{ $menu->url() ?? '#' }}"
                @if ($isActive) aria-current="page" @endif>
                <span class="rm-nav-label">{{ $label }}</span>
            </a>
        @endif
    @endif
@endif
