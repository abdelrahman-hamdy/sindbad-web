<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', 'en');
        $locale = in_array($locale, ['ar', 'en']) ? $locale : 'en';
        app()->setLocale($locale);
        return $next($request);
    }
}
