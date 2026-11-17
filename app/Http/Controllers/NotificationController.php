<?php

namespace App\Http\Controllers;

use App\Helpers\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * In-app notifications of the signed-in user: the latest in the navbar bell, all of them in the Inbox page, where they
 * are read, marked as read or deleted. Everyone only reaches their own.
 */
class NotificationController extends Controller
{
    /**
     * Notifications on a page of the inbox.
     */
    public const PER_PAGE = 15;

    /**
     * The Inbox: every notification of the user, newest first, or the unread ones (?filter=unread). A page left
     * empty by a deletion sends on to the last page that still has some.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $unreadOnly = $request->query('filter') === 'unread';
        $notifications = ($unreadOnly ? $user->unreadNotifications() : $user->notifications())
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        if ($notifications->isEmpty() && $notifications->currentPage() > 1) {
            return redirect($notifications->url($notifications->lastPage()));
        }

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadOnly' => $unreadOnly,
            'unreadCount' => $user->unreadNotifications()->count(),
            'total' => $user->notifications()->count(),
        ]);
    }

    /**
     * Open a notification: mark it as read and go to its page.
     */
    public function show(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        $url = (string) ($item->data['url'] ?? '');

        // Only follow links inside the application.
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return redirect($url);
        }

        return redirect($request->user()->homeUrl());
    }

    /**
     * Mark one notification as read without opening it.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return back();
    }

    /**
     * Mark every notification of the user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * Delete one notification from the inbox.
     */
    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->delete();
        Alert::success(__('Notification deleted.'));

        return back();
    }
}
