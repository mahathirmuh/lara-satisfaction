<?php

namespace App\Helpers;

/**
 * Flash a SweetAlert message for the next page load.
 *
 * Rendered by resources/views/main/modal/alert.blade.php (and the auth
 * layout) through @json, so the message is always safely encoded.
 */
class Alert
{
    /**
     * Flash a success message.
     */
    public static function success(string $title, ?string $text = null): void
    {
        static::flash('success', $title, $text);
    }

    /**
     * Flash an error message.
     */
    public static function error(string $title, ?string $text = null): void
    {
        static::flash('error', $title, $text);
    }

    /**
     * Flash a warning message.
     */
    public static function warning(string $title, ?string $text = null): void
    {
        static::flash('warning', $title, $text);
    }

    /**
     * Flash an informational message.
     */
    public static function info(string $title, ?string $text = null): void
    {
        static::flash('info', $title, $text);
    }

    /**
     * Store the alert in the session flash data.
     */
    protected static function flash(string $icon, string $title, ?string $text): void
    {
        session()->flash('alert', [
            'icon' => $icon,
            'title' => $title,
            'text' => $text ?? '',
        ]);
    }
}
