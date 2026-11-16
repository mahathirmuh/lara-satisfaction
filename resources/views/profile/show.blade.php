@extends('main.index')

@section('page-title', __('My Profile'))

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center py-4">
                    <span class="avatar avatar-xl rounded-circle mb-3"
                        style="background-image: url({{ $user->photoUrl() ?? asset('assets/img/avatar/1.png') }})"></span>
                    <h3 class="mb-1">{{ $user->name }}</h3>
                    <div class="text-secondary text-break">{{ $user->email }}</div>
                    @if ($user->job_title)
                        <div class="mt-2 fw-medium">{{ $user->job_title }}</div>
                    @endif
                    <div class="mt-3">
                        <span @class(['badge', 'bg-green-lt' => $user->is_active, 'bg-red-lt' => ! $user->is_active])>
                            {{ $user->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-language me-1"></i> {{ __('Language') }}</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (config('app.available_locales') as $locale => $language)
                            <form method="POST" action="{{ route('locale.update') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $locale }}">
                                <button type="submit" lang="{{ str_replace('_', '-', $locale) }}" @class([
                                    'btn btn-sm',
                                    'mdka-btn-brand-500 text-white border-0' => app()->getLocale() === $locale,
                                    'btn-outline-secondary' => app()->getLocale() !== $locale,
                                ])>
                                    {{ $language }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                    <small class="form-hint mt-2">{{ __('Saved on your account: it is also used on your other devices and for your notifications.') }}</small>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
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
                    <div class="alert alert-info mt-4 mb-0 py-2" role="alert">
                        <i class="ti ti-info-circle me-1"></i>
                        {{ __('Your name, job title, department and employee ID come from the company directory (Active Directory / Microsoft) and are refreshed every day and every time you sign in. Contact the IT Helpdesk if something is wrong.') }}
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-shield-lock me-1"></i> {{ __('Access') }}</h4>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Role') }}</div>
                            <div class="datagrid-content d-flex flex-wrap gap-1">
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
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Survey Results You Can See') }}</div>
                            <div class="datagrid-content">
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
                </div>
            </div>
        </div>
    </div>
@endsection
