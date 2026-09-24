<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the player's chosen quiz language from `?lang=` and activates it
 * for the request, so translatable model attributes (e.g. `Question`) resolve
 * to that locale.
 */
class SetLocaleFromRequest
{
    /**
     * @var array<string>
     */
    public const SUPPORTED_LOCALES = ['en', 'hr', 'nl', 'se'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->string('lang')->toString();

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
