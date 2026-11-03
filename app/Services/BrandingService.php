<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
    public const PRODUCT_NAME = 'Absensi Zaiv';
    public const PRODUCT_TAGLINE = 'Sistem Presensi & Manajemen Kerja';

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

    /**
     * Product Identity Accessor (Fixed / Centrally Defined)
     */
    public function getProductName(): string
    {
        return self::PRODUCT_NAME;
    }

    /**
     * Product Tagline Accessor (Fixed / Centrally Defined)
     */
    public function getProductTagline(): string
    {
        return self::PRODUCT_TAGLINE;
    }

    /**
     * Legacy App Name Accessor (Backward-Compatible with database setting & config)
     */
    public function getAppName(): string
    {
        $dbAppName = trim((string) $this->getSetting('app_name', ''));
        if ($dbAppName !== '') {
            return $dbAppName;
        }

        $configName = config('app.name');
        if ($configName && ! in_array($configName, ['Laravel', 'Attendance & Scheduling', 'SELON BEAUTY Attendance'], true)) {
            return $configName;
        }

        return self::PRODUCT_NAME;
    }

    /**
     * Organization Short Name Accessor
     */
    public function getAppShortName(): string
    {
        $shortName = trim((string) $this->getSetting('app_short_name', ''));
        if ($shortName !== '') {
            return $shortName;
        }

        $companyName = trim((string) $this->getSetting('company_name', ''));
        if ($companyName !== '') {
            return $companyName;
        }

        return self::PRODUCT_NAME;
    }

    /**
     * Organization / Company Name Resolution
     *
     * Resolution order:
     * 1. Configured company_name in app_settings
     * 2. Configured app_short_name in app_settings
     * 3. Configured app_name in app_settings
     * 4. Fallback to generic organization label or config
     */
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

        return config('app.company_name', 'Perusahaan');
    }

    /**
     * Alias for Organization Name
     */
    public function getOrganizationName(): string
    {
        return $this->getCompanyName();
    }

    /**
     * Organization Tagline Accessor
     */
    public function getAppTagline(): string
    {
        return $this->getSetting('app_tagline', self::PRODUCT_TAGLINE);
    }

    /**
     * Dynamic PWA Application Name
     */
    public function getPwaName(): string
    {
        $dbAppName = trim((string) $this->getSetting('app_name', ''));
        if ($dbAppName !== '') {
            return $dbAppName;
        }

        $companyName = trim((string) $this->getSetting('company_name', ''));
        if ($companyName !== '' && $companyName !== 'Perusahaan') {
            return "{$companyName} (" . self::PRODUCT_NAME . ')';
        }

        return self::PRODUCT_NAME;
    }

    /**
     * Dynamic PWA Short Name
     */
    public function getPwaShortName(): string
    {
        $dbShortName = trim((string) $this->getSetting('app_short_name', ''));
        if ($dbShortName !== '') {
            return $dbShortName;
        }

        $companyName = trim((string) $this->getSetting('company_name', ''));
        if ($companyName !== '' && $companyName !== 'Perusahaan') {
            return $companyName;
        }

        return self::PRODUCT_NAME;
    }

    public function getBrandPrimary(): string
    {
        $color = $this->getSetting('brand_primary', '#0F766E');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#0F766E';
    }

    public function getBrandAccent(): string
    {
        $color = $this->getSetting('brand_accent', '#0284C7');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#0284C7';
    }

    public function getPwaThemeColor(): string
    {
        $color = $this->getSetting('pwa_theme_color', $this->getBrandPrimary());

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : $this->getBrandPrimary();
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
     * Format a standard browser title: {pageTitle} · {organization_name} — {product_name}
     */
    public function formatBrowserTitle(string $pageTitle): string
    {
        $org = $this->getCompanyName();
        $product = $this->getProductName();

        if (trim($pageTitle) === '') {
            return "{$org} — {$product}";
        }

        return "{$pageTitle} · {$org} — {$product}";
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
            'product_name' => $this->getProductName(),
            'product_tagline' => $this->getProductTagline(),
            'company_name' => $this->getCompanyName(),
            'organization_name' => $this->getOrganizationName(),
            'app_name' => $this->getAppName(),
            'app_short_name' => $this->getAppShortName(),
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
