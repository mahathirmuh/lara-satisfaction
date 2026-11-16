<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the interface language of the request: the one chosen in this session,
 * else the one saved on the signed-in user's account, else the browser's
 * preferred language when it is available, else the default locale.
 */
class SetLocale
{
    /**
     * Session key holding the chosen locale.
     */
    public const SESSION_KEY = 'locale';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->localeFor($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    /**
     * Whether the interface is available in this locale.
     */
    public static function isAvailable(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, config('app.available_locales', []));
    }

    /**
     * The locale to use for this request.
     */
    protected function localeFor(Request $request): string
    {
        foreach ([
            $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null,
            $request->user()?->locale,
        ] as $chosen) {
            if (self::isAvailable($chosen)) {
                return $chosen;
            }
        }

        $available = array_keys(config('app.available_locales', []));
        $preferred = $request->getPreferredLanguage($available);

        return $preferred !== null && $request->headers->has('Accept-Language') && self::isAvailable($preferred)
            ? $preferred
            : (string) config('app.locale');
    }
}
