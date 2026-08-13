<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function __invoke(BrandingService $brandingService): JsonResponse
    {
        $appName = $brandingService->getAppName();
        $appShortName = $brandingService->getAppShortName();
        $themeColor = $brandingService->getPwaThemeColor();
        $iconUrl = $brandingService->getAppIconUrl();

        $manifestData = [
            'name' => $appName,
            'short_name' => $appShortName,
            'description' => $brandingService->getAppTagline(),
            'start_url' => '/app/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => $themeColor,
            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ];

        return response()->json($manifestData, 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
