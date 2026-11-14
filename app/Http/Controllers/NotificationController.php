<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * In-app notifications of the signed-in user (navbar bell).
 */
class NotificationController extends Controller
{
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
     * Mark every notification of the user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
