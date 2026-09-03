<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class GasMeterPhotoThumb
{
    /**
     * Build a small JPEG data URI for print/HTML embedding (no auth URL needed).
     */
    public static function dataUri(?string $photoPath, int $maxEdge = 240, int $quality = 72): ?string
    {
        if (! filled($photoPath) || ! Storage::disk('public')->exists($photoPath)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($photoPath);
        if (! is_readable($absolute)) {
            return null;
        }

        $binary = @file_get_contents($absolute);
        if ($binary === false || $binary === '') {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return null;
        }

        $scale = min(1, $maxEdge / max($width, $height));
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($targetW, $targetH);
        if ($thumb === false) {
            imagedestroy($source);

            return null;
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
        imagedestroy($source);

        ob_start();
        imagejpeg($thumb, null, $quality);
        $jpeg = ob_get_clean();
        imagedestroy($thumb);

        if ($jpeg === false || $jpeg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }
}
