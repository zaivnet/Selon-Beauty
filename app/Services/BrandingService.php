<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
    public const MEDIA_SETTINGS = [
        'logo' => 'app_logo_path',
        'pwa-icon' => 'app_icon_path',
        'favicon' => 'app_favicon_path',
    ];

    protected ?array $cachedSettings = null;

    public function clearCache(): void
    {
        $this->cachedSettings = null;
    }

    protected function getSetting(string $key, mixed $default = null): mixed
    {
        if ($this->cachedSettings === null) {
            $this->cachedSettings = AppSetting::all()->pluck('value', 'key')->toArray();
        }

        return $this->cachedSettings[$key] ?? $default;
    }

    public function getAppName(): string
    {
        return $this->getSetting('app_name', config('app.name', 'Attendance & Scheduling'));
    }

    public function getAppShortName(): string
    {
        return $this->getSetting('app_short_name', 'SELON BEAUTY');
    }

    public function getCompanyName(): string
    {
        $companyName = trim((string) $this->getSetting('company_name', ''));
        if ($companyName !== '') {
            return $companyName;
        }

        $appShortName = trim((string) $this->getSetting('app_short_name', ''));
        if ($appShortName !== '') {
            return $appShortName;
        }

        $appName = trim((string) $this->getSetting('app_name', ''));
        if ($appName !== '') {
            return $appName;
        }

        return 'SELON';
    }

    public function getAppTagline(): string
    {
        return $this->getSetting('app_tagline', 'Sistem Presensi & Kehadiran Moderen');
    }

    public function getBrandPrimary(): string
    {
        $color = $this->getSetting('brand_primary', '#e11d48');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#e11d48';
    }

    public function getBrandAccent(): string
    {
        $color = $this->getSetting('brand_accent', '#f43f5e');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#f43f5e';
    }

    public function getPwaThemeColor(): string
    {
        $color = $this->getSetting('pwa_theme_color', '#e11d48');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#e11d48';
    }

    public function getAppLogoUrl(): ?string
    {
        return $this->mediaUrl('logo');
    }

    public function getAppIconUrl(): string
    {
        return $this->mediaUrl('pwa-icon') ?? asset('icons/icon-192x192.png');
    }

    public function getFaviconUrl(): string
    {
        return $this->mediaUrl('favicon') ?? asset('favicon.ico');
    }

    public function getFaviconMimeType(): string
    {
        $path = $this->getMediaPath('favicon');

        return $path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'png'
            ? 'image/png'
            : 'image/x-icon';
    }

    public function getMediaPath(string $type): ?string
    {
        $setting = self::MEDIA_SETTINGS[$type] ?? null;
        if (! $setting) {
            return null;
        }

        $path = str_replace('\\', '/', (string) $this->getSetting($setting, ''));
        if ($path === '' || ! str_starts_with($path, 'branding/') || str_contains($path, '../')) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    protected function mediaUrl(string $type): ?string
    {
        $path = $this->getMediaPath($type);

        return $path
            ? route('branding.media', ['type' => $type, 'v' => substr(sha1($path), 0, 12)], false)
            : null;
    }

    /**
     * Get all branding properties as array for views / manifest / notifications.
     */
    public function getBranding(): array
    {
        return $this->getBrandingData();
    }

    /**
     * Get all branding properties as array for views / manifest.
     */
    public function getBrandingData(): array
    {
        return [
            'app_name' => $this->getAppName(),
            'app_short_name' => $this->getAppShortName(),
            'company_name' => $this->getCompanyName(),
            'app_tagline' => $this->getAppTagline(),
            'brand_primary' => $this->getBrandPrimary(),
            'brand_accent' => $this->getBrandAccent(),
            'pwa_theme_color' => $this->getPwaThemeColor(),
            'app_logo_url' => $this->getAppLogoUrl(),
            'app_icon_url' => $this->getAppIconUrl(),
            'favicon_url' => $this->getFaviconUrl(),
            'favicon_mime_type' => $this->getFaviconMimeType(),
        ];
    }
}
