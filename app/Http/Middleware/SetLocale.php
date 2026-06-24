<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if ($locale && in_array($locale, config('app.supported_locales', ['en', 'it']), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
