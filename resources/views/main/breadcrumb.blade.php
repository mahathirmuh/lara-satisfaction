{{-- The path to the page, at the top of the content under the top bar: "Dashboard › Survey Management › Reminders" (as in
     MTI Radio Management). What comes before the page is grey, a link where it leads somewhere; the page itself is dark.
     A page may name itself here (@section('crumb')), else its title is used; a greeting (@section('welcome')) takes the
     place of the path. On a phone the heading of the page follows, as the top bar that has it is hidden there.
     Data: App\View\Composers\BreadcrumbComposer. --}}
@hasSection('welcome')
    <p class="rm-title rm-welcome">@yield('welcome')</p>
@else
    <nav class="rm-crumbbar" aria-label="{{ __('Breadcrumb') }}">
        <ol>
            @foreach ($crumbs as $crumb)
                <li>
                    @unless ($loop->first)
                        <i class="ti ti-chevron-right" aria-hidden="true"></i>
                    @endunless
                    @if ($crumb['url'] !== null)
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span @if ($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    @if ($heading !== '')
        <div class="rm-title-phone d-lg-none">
            <h1 class="rm-title">{!! $heading !!}</h1>
        </div>
    @endif
@endif
