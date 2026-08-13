<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
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
        return $this->getSetting('app_short_name', config('app.name', 'Attendance & Scheduling'));
    }

    public function getCompanyName(): string
    {
        return $this->getSetting('company_name', config('app.name', 'Attendance & Scheduling'));
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
        $path = $this->getSetting('app_logo_path');
        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        return null;
    }

    public function getAppIconUrl(): string
    {
        $path = $this->getSetting('app_icon_path');
        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        return asset('icons/icon-192x192.png');
    }

    public function getFaviconUrl(): string
    {
        $path = $this->getSetting('app_favicon_path');
        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        return asset('favicon.ico');
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
        ];
    }
}
