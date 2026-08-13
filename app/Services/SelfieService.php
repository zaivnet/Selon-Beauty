<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SelfieService
{
    /**
     * Maximum allowed raw payload size in bytes (5 MB).
     */
    protected int $maxSizeBytes = 5 * 1024 * 1024;

    /**
     * Maximum allowed dimension for image resizing (width or height).
     */
    protected int $maxDimension = 1280;

    /**
     * Allowed MIME types for selfie images.
     */
    protected array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Process, validate, optimize, and store selfie image in private storage disk.
     *
     * @param  UploadedFile|string|null  $input  UploadedFile or Base64 Data URL
     * @param  string  $type  ('check_in' or 'check_out')
     * @return string Relative path stored in private disk
     *
     * @throws \InvalidArgumentException
     */
    public function processAndStore(mixed $input, int $employeeId, string $type = 'check_in', string $category = 'attendance'): string
    {
        if (empty($input)) {
            throw new \InvalidArgumentException('Foto selfie wajib diambil untuk melakukan absensi.');
        }

        $binaryData = null;

        if ($input instanceof UploadedFile) {
            if (! $input->isValid()) {
                throw new \InvalidArgumentException('File foto selfie yang diunggah tidak valid.');
            }

            if ($input->getSize() > $this->maxSizeBytes) {
                throw new \InvalidArgumentException('Ukuran foto selfie terlalu besar. Maksimal 5 MB.');
            }

            $binaryData = file_get_contents($input->getRealPath());
        } elseif (is_string($input)) {
            $inputTrimmed = trim($input);

            if (str_starts_with($inputTrimmed, 'data:image/')) {
                if (! preg_match('/^data:(image\/(jpeg|png|webp));base64,(.*)$/i', $inputTrimmed, $matches)) {
                    throw new \InvalidArgumentException('Format foto selfie base64 tidak didukung. Gunakan JPG, PNG, atau WebP.');
                }
                $binaryData = base64_decode($matches[3], true);
            } else {
                $binaryData = base64_decode($inputTrimmed, true);
            }

            if ($binaryData === false || empty($binaryData)) {
                throw new \InvalidArgumentException('Data foto selfie corrupt atau gagal didekode.');
            }

            if (strlen($binaryData) > $this->maxSizeBytes) {
                throw new \InvalidArgumentException('Ukuran foto selfie terlalu besar. Maksimal 5 MB.');
            }
        } else {
            throw new \InvalidArgumentException('Format foto selfie tidak dikenali.');
        }

        // Validate image content & MIME type using GD / getimagesize
        $imageInfo = @getimagesizefromstring($binaryData);
        if (! $imageInfo) {
            throw new \InvalidArgumentException('Content file bukan merupakan gambar yang valid.');
        }

        $detectedMime = strtolower($imageInfo['mime'] ?? '');
        if (! in_array($detectedMime, $this->allowedMimes, true)) {
            throw new \InvalidArgumentException('Format foto tidak diperbolehkan. Hanya JPG, PNG, dan WebP yang diizinkan.');
        }

        // Re-verify GD can load the image
        $sourceImage = @imagecreatefromstring($binaryData);
        if (! $sourceImage) {
            throw new \InvalidArgumentException('Gagal memproses file foto selfie.');
        }

        // Optimize (resize & compress)
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        $targetWidth = $origWidth;
        $targetHeight = $origHeight;

        $maxDim = max($origWidth, $origHeight);
        if ($maxDim > $this->maxDimension) {
            $ratio = $this->maxDimension / $maxDim;
            $targetWidth = (int) round($origWidth * $ratio);
            $targetHeight = (int) round($origHeight * $ratio);
        }

        $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

        // Fill background with white before copying to handle alpha channels cleanly
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);

        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $targetWidth,
            $targetHeight,
            $origWidth,
            $origHeight
        );

        ob_start();
        imagejpeg($resizedImage, null, 80);
        $compressedBinary = ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        if (! $compressedBinary) {
            throw new \InvalidArgumentException('Gagal mengompresi foto selfie.');
        }

        // Generate random filename & path inside storage/app/private
        // Path format: attendance/{employee_id}/{YYYY}/{MM}/{uuid}.jpg
        $now = Carbon::now(config('app.timezone'));
        $year = $now->format('Y');
        $month = $now->format('m');
        $filename = Str::uuid()->toString().'.jpg';
        if (! in_array($category, ['attendance', 'overtime'], true)) {
            throw new \InvalidArgumentException('Kategori penyimpanan selfie tidak valid.');
        }

        $path = "{$category}/{$employeeId}/{$year}/{$month}/{$filename}";

        Storage::disk('local')->put($path, $compressedBinary);

        return $path;
    }
}
