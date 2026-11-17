@extends('main.index')

@section('page-title', __('My Profile'))

@push('style')
    <style>
        .profile-photo {
            --tblr-avatar-size: 5.5rem;
            flex: 0 0 auto;
            box-shadow: 0 0 0 4px var(--tblr-bg-surface, #fff), 0 0 0 5px var(--tblr-border-color, #e6e7e9);
        }

        .profile-note {
            color: var(--tblr-secondary);
            font-size: .8125rem;
        }
    </style>
@endpush

@section('content')
    <div class="row g-3">
        {{-- Who: the photo, the name, the e-mail and the job title from the directory, and whether the account is active. --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                    <span class="avatar rounded-circle profile-photo"
                        style="background-image: url({{ $user->photoUrl() ?? asset('assets/img/avatar/1.png') }})"></span>
                    <div class="flex-grow-1 min-w-0">
                        <h2 class="mb-1">{{ $user->name }}</h2>
                        <div class="text-secondary text-break"><i class="ti ti-mail me-1" aria-hidden="true"></i>{{ $user->email }}</div>
                        @if ($user->job_title)
                            <div class="mt-1 fw-medium"><i class="ti ti-briefcase me-1 text-secondary" aria-hidden="true"></i>{{ $user->job_title }}</div>
                        @endif
                    </div>
                    <span @class(['badge align-self-sm-start', 'bg-green-lt' => $user->is_active, 'bg-red-lt' => ! $user->is_active])>
                        {{ $user->is_active ? __('Active') : __('Inactive') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- What the directory says about the account. --}}
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-id me-1"></i> {{ __('Account Details') }}</h4>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Employee ID') }}</div>
                            <div class="datagrid-content">{{ $user->employee_id ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Job Title') }}</div>
                            <div class="datagrid-content">{{ $user->job_title ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Company') }}</div>
                            <div class="datagrid-content">{{ $user->company?->name ?? '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Department') }}</div>
                            <div class="datagrid-content">{{ $user->department?->name ?? '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Last Login') }}</div>
                            <div class="datagrid-content">
                                {{ $user->last_login_at?->translatedFormat(__('d M Y H:i')) ?? __('Never') }}
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Surveys Completed') }}</div>
                            <div class="datagrid-content">{{ $surveysCompleted }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer profile-note py-2 px-3">
                    <i class="ti ti-info-circle me-1" aria-hidden="true"></i>
                    {{ __('Your name, job title, department and employee ID come from the company directory (Active Directory / Microsoft) and are refreshed every day and every time you sign in. Contact the IT Helpdesk if something is wrong.') }}
                </div>
            </div>
        </div>

        {{-- What the account may do, and the language of the screens. --}}
        <div class="col-xl-4 d-flex flex-column gap-3">
            <div class="card">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-shield-lock me-1"></i> {{ __('Access') }}</h4>
                </div>
                <div class="card-body">
                    <div class="datagrid-title">{{ __('Role') }}</div>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        @forelse ($roles as $role)
                            <span class="badge bg-blue-lt">
                                {{ $role->name }}
                                @if ($role->isGivenByJobTitle())
                                    <span class="text-secondary">· {{ __('by job title') }}</span>
                                @endif
                            </span>
                        @empty
                            -
                        @endforelse
                    </div>
                    <div class="datagrid-title">{{ __('Survey Results You Can See') }}</div>
                    <div>
                        @if ($seesAllResults)
                            {{ __('All departments') }}
                        @elseif ($resultDepartments->isNotEmpty())
                            {{ $resultDepartments->implode(', ') }}
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>

            <div class="card flex-grow-1">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-language me-1"></i> {{ __('Language') }}</h4>
                </div>
                <div class="card-body">
                    {{-- As the EN | ID | 中文 switch of the top bar (.rm-seg), with the full names. --}}
                    <form method="POST" action="{{ route('locale.update') }}" class="rm-seg flex-wrap" aria-label="{{ __('Language') }}">
                        @csrf
                        @foreach (config('app.available_locales') as $locale => $language)
                            <button type="submit" name="locale" value="{{ $locale }}" lang="{{ str_replace('_', '-', $locale) }}"
                                aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                                @class(['is-on' => app()->getLocale() === $locale])>{{ $language }}</button>
                        @endforeach
                    </form>
                    <div class="profile-note mt-2">
                        {{ __('Saved on your account: it is also used on your other devices and for your notifications.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
