{{-- The Inbox: every in-app notification of whoever is signed in (NotificationController@index), newest first. --}}
@extends('main.index')

@section('page-title', __('Inbox'))

@push('style')
    <style>
        .inbox-item.is-unread {
            background: var(--tblr-primary-lt, #eff6ff);
        }

        .inbox-item.is-unread .inbox-title {
            font-weight: 600;
        }

        .inbox-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--tblr-primary);
            display: inline-block;
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header p-2 px-3 d-flex flex-wrap align-items-center gap-2">
            <ul class="nav nav-pills" role="tablist" aria-label="{{ __('Show') }}">
                <li class="nav-item">
                    <a href="{{ route('notifications.index') }}" @class(['nav-link', 'active' => ! $unreadOnly]) @if (! $unreadOnly) aria-current="page" @endif>
                        {{ __('All') }} <span class="badge bg-secondary-lt ms-1">{{ $total }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" @class(['nav-link', 'active' => $unreadOnly]) @if ($unreadOnly) aria-current="page" @endif>
                        {{ __('Unread') }} <span class="badge bg-blue-lt ms-1">{{ $unreadCount }}</span>
                    </a>
                </li>
            </ul>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="ms-auto">
                    @csrf
                    <x-global.btn-detail :label="__('Mark all as read')" color="blue" size="sm" outline="true" type="submit">
                        <x-slot:icon><i class="ti ti-checks"></i></x-slot:icon>
                    </x-global.btn-detail>
                </form>
            @endif
        </div>

        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php
                    $isReminder = $notification->type === \App\Notifications\SurveyDeadlineReminder::class;
                @endphp
                <div @class(['list-group-item inbox-item', 'is-unread' => ! $notification->read_at])>
                    <div class="row align-items-center g-3">
                        <div class="col-auto">
                            <span class="avatar {{ $isReminder ? 'bg-orange-lt' : 'bg-blue-lt' }}" aria-hidden="true">
                                <i class="ti {{ $isReminder ? 'ti-calendar-due' : 'ti-bell' }}"></i>
                            </span>
                        </div>
                        <div class="col min-w-0">
                            <a href="{{ route('notifications.show', $notification->id) }}" class="text-reset d-block inbox-title">
                                @unless ($notification->read_at)
                                    <span class="inbox-dot me-1" aria-label="{{ __('Unread') }}"></span>
                                @endunless
                                {{ $notification->data['title'] ?? __('Notification') }}
                            </a>
                            @if (filled($notification->data['message'] ?? null))
                                <div class="text-secondary">{{ $notification->data['message'] }}</div>
                            @endif
                            <div class="small text-secondary mt-1" title="{{ $notification->created_at->translatedFormat(__('d M Y H:i')) }}">
                                <i class="ti ti-clock me-1" aria-hidden="true"></i>{{ $notification->created_at->diffForHumans() }}
                                · {{ $notification->created_at->translatedFormat(__('d M Y H:i')) }}
                            </div>
                        </div>
                        <div class="col-auto d-flex align-items-center gap-1">
                            <a href="{{ route('notifications.show', $notification->id) }}" class="btn btn-sm btn-outline-blue">
                                {{ __('Open') }}
                            </a>
                            @unless ($notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-secondary" title="{{ __('Mark as read') }}"
                                        aria-label="{{ __('Mark as read') }}" data-bs-toggle="tooltip">
                                        <i class="ti ti-mail-opened" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="inbox-delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="{{ __('Delete') }}"
                                    aria-label="{{ __('Delete') }}" data-bs-toggle="tooltip">
                                    <i class="ti ti-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item">
                    <x-global.empty-state icon="bell-off" :title="$unreadOnly ? __('No unread notifications.') : __('No notifications yet.')"
                        :subtitle="__('Survey reminders and other news for you appear here.')" />
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-footer d-flex justify-content-end">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection

@push('javascript')
    <script>
        // Deleting asks first, as everywhere else.
        $('.inbox-delete').on('submit', function(event) {
            event.preventDefault();
            const form = this;

            confirmDelete(@json(__('This notification will be deleted.'))).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
