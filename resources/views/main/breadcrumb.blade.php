{{-- Breadcrumb of the current page: home, the menu path of the page and the items the page pushes onto the "breadcrumbs" stack (x-global.breadcrumb-item). Without a current item the page title closes it. Data: App\View\Composers\BreadcrumbComposer. --}}
@php($hasPageItems = ! $__env->isStackEmpty('breadcrumbs'))
@php($hasCurrentMenu = collect($trail)->contains('current', true))
<nav aria-label="{{ __('Breadcrumb') }}" class="mb-2">
    <ol class="breadcrumb breadcrumb-arrows breadcrumb-muted mb-0 small">
        <li class="breadcrumb-item">
            <a href="{{ $homeUrl }}" title="{{ __('Home') }}" aria-label="{{ __('Home') }}"><i class="ti ti-home"></i></a>
        </li>
        @foreach ($trail as $item)
            @if ($item['current'] && ! $hasPageItems)
                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
            @elseif ($item['url'])
                <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
            @else
                <li class="breadcrumb-item">{{ $item['label'] }}</li>
            @endif
        @endforeach
        @stack('breadcrumbs')
        @if (! $hasPageItems && ! $hasCurrentMenu)
            <li class="breadcrumb-item active" aria-current="page">@yield('page-title')</li>
        @endif
    </ol>
</nav>
