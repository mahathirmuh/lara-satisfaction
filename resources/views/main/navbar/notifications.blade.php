{{-- The bell: the latest notifications of whoever is signed in, as the panel of MTI Radio Management draws them (.rm-notif-*
     in merdeka-ui.css). Shown in the top bar, and beside the menu button on a phone. Expects $notifications and
     $unreadNotificationCount (App\View\Composers\NotificationComposer). --}}
<div class="dropdown notification-bell">
    <a href="#" class="rm-icon-btn rm-bell" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
        data-bs-offset="0,10" aria-expanded="false" aria-label="{{ __('Show notifications') }}">
        <i class="ti ti-bell" aria-hidden="true"></i>
        @if ($unreadNotificationCount > 0)
            <span class="rm-bell-count">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-end rm-notif-menu">
        <div class="rm-notif-head">
            <h4 class="rm-notif-head-title">{{ __('Notifications') }}</h4>
            @if ($unreadNotificationCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="ms-auto">
                    @csrf
                    <button type="submit" class="rm-notif-readall">{{ __('Mark all as read') }}</button>
                </form>
            @endif
        </div>
        <div class="rm-notif-list">
            @forelse ($notifications as $notification)
                <a href="{{ route('notifications.show', $notification->id) }}" @class(['rm-notif-item', 'is-unread' => ! $notification->read_at])>
                    <span class="rm-notif-disc tone-orange" aria-hidden="true"><i class="ti ti-calendar-due"></i></span>
                    <span class="rm-notif-text">
                        <span class="rm-notif-line">
                            <span class="rm-notif-title">{{ $notification->data['title'] ?? __('Notification') }}</span>
                            <span class="rm-notif-time" title="{{ $notification->created_at->translatedFormat(__('d M Y H:i')) }}">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                        @if (filled($notification->data['message'] ?? null))
                            <span class="rm-notif-body">{{ $notification->data['message'] }}</span>
                        @endif
                    </span>
                    @unless ($notification->read_at)
                        <span class="rm-notif-dot" aria-label="{{ __('Unread') }}"></span>
                    @endunless
                </a>
            @empty
                <div class="rm-notif-empty">{{ __('No notifications yet.') }}</div>
            @endforelse
        </div>
    </div>
</div>
