<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Interface language of the request, in order:
 * the choice made in this session, the user's saved choice, the browser's language
 * (if LOCALE_FROM_BROWSER is true), then APP_LOCALE.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale(self::resolve($request));

        return $next($request);
    }

    public static function resolve(Request $request): string
    {
        $locales = array_keys(config('app.locales'));

        foreach ([$request->session()->get('locale'), $request->user()?->locale] as $choice) {
            if (in_array($choice, $locales, true)) {
                return $choice;
            }
        }

        if (config('app.locale_from_browser')) {
            // Accept-Language in order of preference: "en-GB" counts as "en"
            foreach ($request->getLanguages() as $language) {
                $language = strtolower(strtok(str_replace('_', '-', $language), '-'));
                if (in_array($language, $locales, true)) {
                    return $language;
                }
            }
        }

        return config('app.default_locale');
    }
}
