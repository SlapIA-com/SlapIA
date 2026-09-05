<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reprend la logique de includes/i18n.php : ?lang=fr|en|de en priorité,
 * sinon cookie slapia_lang, sinon fr par défaut. Le cookie est pour 1 an.
 */
class SetLocale
{
    public const SUPPORTED = ['fr', 'en', 'de'];

    public function handle(Request $request, Closure $next): Response
    {
        $lang = $request->query('lang');

        if ($lang && in_array($lang, self::SUPPORTED, true)) {
            app()->setLocale($lang);
            $response = $next($request);
            $response->headers->setCookie(
                cookie('slapia_lang', $lang, 60 * 24 * 365)
            );
            return $response;
        }

        $cookieLang = $request->cookie('slapia_lang');
        if ($cookieLang && in_array($cookieLang, self::SUPPORTED, true)) {
            app()->setLocale($cookieLang);
        } else {
            app()->setLocale('fr');
        }

        return $next($request);
    }
}
