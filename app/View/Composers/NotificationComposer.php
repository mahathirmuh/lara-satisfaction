<?php

namespace App\View\Composers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Supplies the main layout with the latest in-app notifications of the
 * signed-in user for the navbar bell (desktop and mobile header).
 */
class NotificationComposer
{
    /**
     * Number of notifications listed in the bell.
     */
    public const LIMIT = 6;

    /**
     * Create a new composer instance.
     */
    public function __construct(protected Request $request) {}

    /**
     * Bind data to the view. The notifications relation already lists the
     * newest first; ordering it again repeats the column, which SQL Server rejects.
     */
    public function compose(View $view): void
    {
        $user = $this->request->user();

        $view->with([
            'notifications' => $user?->notifications()->limit(self::LIMIT)->get() ?? collect(),
            'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
