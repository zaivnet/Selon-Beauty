<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function __invoke(BrandingService $brandingService): JsonResponse
    {
        $manifestName = $brandingService->getPwaName();
        $manifestShortName = $brandingService->getPwaShortName();
        $themeColor = $brandingService->getPwaThemeColor();

        $customIconUrl = $brandingService->getMediaPath('pwa-icon')
            ? $brandingService->getAppIconUrl()
            : null;

        $icons = [
            [
                'src' => $customIconUrl ?? asset('icons/icon-192x192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $customIconUrl ?? asset('icons/icon-512x512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $customIconUrl ?? asset('icons/maskable-icon-512x512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];

        $manifestData = [
            'name' => $manifestName,
            'short_name' => $manifestShortName,
            'description' => $brandingService->getAppTagline(),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => $themeColor,
            'icons' => $icons,
        ];

        return response()->json($manifestData, 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
