<?php

namespace App\Services;

class AppVersionService
{
    protected static ?string $cachedVersion = null;

    /**
     * Resolve normalized application version from project root VERSION file or safe fallback.
     * Does NOT execute shell commands or query Git at runtime.
     */
    public static function getVersion(): string
    {
        if (static::$cachedVersion !== null) {
            return static::$cachedVersion;
        }

        $versionFile = base_path('VERSION');

        if (is_file($versionFile) && is_readable($versionFile)) {
            $raw = @file_get_contents($versionFile);
            if ($raw !== false && trim($raw) !== '') {
                return static::$cachedVersion = trim($raw);
            }
        }

        return static::$cachedVersion = '1.0.0';
    }

    /**
     * Clear memory cache (useful for testing).
     */
    public static function clearCache(): void
    {
        static::$cachedVersion = null;
    }

    /**
     * Instance wrapper.
     */
    public function version(): string
    {
        return static::getVersion();
    }
}
