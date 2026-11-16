@props(['align' => 'end'])

{{-- Interface language dropdown (English, Bahasa Indonesia, 简体中文). --}}
<div {{ $attributes->merge(['class' => 'dropdown']) }}>
    <a href="#" class="nav-link d-flex align-items-center gap-1 text-reset px-0" data-bs-toggle="dropdown"
        aria-label="{{ __('Change language') }}" title="{{ __('Change language') }}">
        <i class="ti ti-language fs-2"></i>
        <span class="small fw-medium text-uppercase">{{ str_replace('_CN', '', app()->getLocale()) }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-{{ $align }} dropdown-menu-arrow">
        <span class="dropdown-header">{{ __('Language') }}</span>
        @foreach (config('app.available_locales') as $locale => $language)
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <button type="submit" @class(['dropdown-item', 'active' => app()->getLocale() === $locale])
                    lang="{{ str_replace('_', '-', $locale) }}">
                    {{ $language }}
                </button>
            </form>
        @endforeach
    </div>
</div>
