<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandingMediaController extends Controller
{
    public function __invoke(string $type, BrandingService $brandingService): StreamedResponse
    {
        abort_unless(array_key_exists($type, BrandingService::MEDIA_SETTINGS), 404);

        $path = $brandingService->getMediaPath($type);
        abort_unless($path, 404);

        $disk = Storage::disk('public');
        $mimeType = $disk->mimeType($path);
        if (! $mimeType || $mimeType === 'application/octet-stream') {
            $mimeType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'ico' => 'image/x-icon',
                default => 'application/octet-stream',
            };
        }

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
