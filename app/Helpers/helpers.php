<?php

if (!function_exists('localized_route')) {
    /**
     * Generate a localized route URL.
     * If locale is 'ko' (default), use original routes.
     * Otherwise, use localized.* routes with locale prefix.
     */
    function localized_route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ko') {
            return route($name, $parameters, $absolute);
        }

        $localizedName = 'localized.' . $name;

        if (\Illuminate\Support\Facades\Route::has($localizedName)) {
            return route($localizedName, array_merge(['locale' => $locale], $parameters), $absolute);
        }

        // Fallback to original route
        return route($name, $parameters, $absolute);
    }
}

if (!function_exists('current_locale_prefix')) {
    /**
     * Get the current locale URL prefix.
     * Returns empty string for 'ko', otherwise '/locale'.
     */
    function current_locale_prefix(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ko' ? '' : '/' . $locale;
    }
}
