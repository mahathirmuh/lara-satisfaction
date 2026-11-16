{{-- Notification bell. Expects $notifications and $unreadNotificationCount (App\View\Composers\NotificationComposer). --}}
<div class="nav-item dropdown notification-bell">
    <a href="#" class="nav-link px-0 position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside"
        aria-label="{{ __('Show notifications') }}">
        <i class="ti ti-bell fs-2"></i>
        @if ($unreadNotificationCount > 0)
            <span class="badge bg-red text-white badge-notification badge-pill">
                {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
            </span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
        <div class="card notification-card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h3 class="card-title mb-0">{{ __('Notifications') }}</h3>
                @if ($unreadNotificationCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm p-0">{{ __('Mark all as read') }}</button>
                    </form>
                @endif
            </div>
            <div class="list-group list-group-flush list-group-hoverable notification-list">
                @forelse ($notifications as $notification)
                    <a href="{{ route('notifications.show', $notification->id) }}"
                        class="list-group-item list-group-item-action py-2 {{ $notification->read_at ? '' : 'mdka-bg-blue-50' }}">
                        <div class="d-flex gap-2">
                            <span
                                class="status-dot {{ $notification->read_at ? 'bg-secondary-lt' : 'status-dot-animated bg-red' }} d-block mt-2 flex-shrink-0"></span>
                            <div class="flex-fill text-wrap">
                                <div class="fw-bold text-body">{{ $notification->data['title'] ?? __('Notification') }}</div>
                                <div class="text-secondary small">{{ $notification->data['message'] ?? '' }}</div>
                                <div class="text-secondary small mt-1">
                                    <i class="ti ti-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-secondary text-center py-4">
                        <i class="ti ti-bell-off fs-2 d-block mb-1"></i>{{ __('No notifications yet.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
