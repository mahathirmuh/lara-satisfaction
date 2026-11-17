<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer: as in the HTML part (html/message.blade.php) --}}
    <x-slot:footer>
        <x-mail::footer>
            {{ __('This e-mail was sent automatically by :app.', ['app' => config('app.name')]) }}
            Merdeka Copper Gold · Merdeka Tsingshan Indonesia · Merdeka Battery Materials
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
