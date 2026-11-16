@props(['href' => null, 'current' => false])

{{-- One breadcrumb item a page adds after its menu path: @push('breadcrumbs') <x-global.breadcrumb-item current>{{ $survey->title }}</x-global.breadcrumb-item> @endpush --}}
<li {{ $attributes->class(['breadcrumb-item', 'active' => $current]) }} @if ($current) aria-current="page" @endif>@if ($href && ! $current)<a href="{{ $href }}">{{ trim($slot) }}</a>@else{{ trim($slot) }}@endif</li>
