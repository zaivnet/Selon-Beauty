<?php

function generatePwaIcon(int $size, string $outputPath, bool $isMaskable = false): void
{
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    // Rose Background Gradient Color (#e11d48)
    $roseBg = imagecolorallocate($img, 225, 29, 72);
    $roseDark = imagecolorallocate($img, 159, 18, 57);
    $white = imagecolorallocate($img, 255, 255, 255);
    $gold = imagecolorallocate($img, 254, 240, 138);

    // Fill Background
    imagefilledrectangle($img, 0, 0, $size, $size, $roseBg);

    // Draw inner circle accent
    $padding = $isMaskable ? (int)($size * 0.15) : 0;
    $cx = (int)($size / 2);
    $cy = (int)($size / 2);
    $radius = (int)(($size - ($padding * 2)) * 0.45);

    imagefilledellipse($img, $cx, $cy, $radius * 2, $radius * 2, $roseDark);

    // Draw text "SB" centered
    $fontSize = (int)($size * 0.28);
    // Use GD internal font or imagestring for crisp fallback if no TTF
    $text = "SB";
    
    // Check if TTF font exists in system or use built-in
    $fontFile = 'C:\\Windows\\Fonts\\arialbd.ttf';
    if (file_exists($fontFile)) {
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = abs($bbox[4] - $bbox[0]);
        $textHeight = abs($bbox[5] - $bbox[1]);
        $x = (int)($cx - ($textWidth / 2));
        $y = (int)($cy + ($textHeight / 3));
        imagettftext($img, $fontSize, 0, $x, $y, $gold, $fontFile, $text);
    } else {
        $font = 5; // Builtin font 5
        $tw = imagefontwidth($font) * strlen($text);
        $th = imagefontheight($font);
        imagestring($img, $font, (int)($cx - $tw / 2), (int)($cy - $th / 2), $text, $gold);
    }

    // Draw "SELON BEAUTY" text at bottom if size >= 192
    if ($size >= 192 && file_exists($fontFile)) {
        $subText = "SELON BEAUTY";
        $subFontSize = (int)($size * 0.055);
        $sBbox = imagettfbbox($subFontSize, 0, $fontFile, $subText);
        $sWidth = abs($sBbox[4] - $sBbox[0]);
        $sx = (int)($cx - ($sWidth / 2));
        $sy = (int)($cy + $radius * 0.7);
        imagettftext($img, $subFontSize, 0, $sx, $sy, $white, $fontFile, $subText);
    }

    imagepng($img, $outputPath);
    imagedestroy($img);
    echo "Generated icon: {$outputPath} ({$size}x{$size})\n";
}

$iconsDir = __DIR__ . '/../public/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

generatePwaIcon(192, $iconsDir . '/icon-192x192.png');
generatePwaIcon(512, $iconsDir . '/icon-512x512.png');
generatePwaIcon(512, $iconsDir . '/maskable-icon-512x512.png', true);
generatePwaIcon(32, $iconsDir . '/favicon-32x32.png');
generatePwaIcon(32, __DIR__ . '/../public/favicon.ico');
