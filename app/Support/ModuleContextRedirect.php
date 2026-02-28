<?php

namespace App\Support;

class ModuleContextRedirect
{
    public static function loginFallback(): string
    {
        $lastUrl = session('module_last_url');
        $homeUrl = session('module_home_url');

        foreach ([$lastUrl, $homeUrl] as $candidate) {
            if (self::isValidModuleUrl($candidate)) {
                return $candidate;
            }
        }

        return route('welcome', absolute: false);
    }

    public static function logoutFallback(): string
    {
        $homeUrl = session('module_home_url');

        if (self::isValidModuleUrl($homeUrl)) {
            return $homeUrl;
        }

        return route('welcome', absolute: false);
    }

    private static function isValidModuleUrl(?string $url): bool
    {
        if (!is_string($url) || trim($url) === '') {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || trim($path, '/') === '') {
            return false;
        }

        $firstSegment = explode('/', trim($path, '/'))[0] ?? null;
        if (!is_string($firstSegment) || $firstSegment === '') {
            return false;
        }

        return !in_array($firstSegment, self::ignoredSegments(), true);
    }

    private static function ignoredSegments(): array
    {
        return [
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'verify-email',
            'confirm-password',
            'profile',
            'storage',
            'build',
            'up',
        ];
    }
}
