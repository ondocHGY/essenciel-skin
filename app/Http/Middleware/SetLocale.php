<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if ($locale && in_array($locale, config('app.available_locales', ['ko']))) {
            app()->setLocale($locale);
        } else {
            app()->setLocale('ko');
        }

        // locale 파라미터를 제거하여 컨트롤러에 전달되지 않게 함
        $request->route()->forgetParameter('locale');

        return $next($request);
    }
}
