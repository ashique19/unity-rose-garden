<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;

class ImageResizer
{
    public const MAX_EDGE = 1200;

    /**
     * Resize an image file in place to fit within maxEdge × maxEdge.
     * Writes JPEG when possible for compression.
     *
     * @return array{width: int, height: int, mime: string, path: string}
     */
    public static function constrain(string $absolutePath, int $maxEdge = self::MAX_EDGE, int $quality = 82): array
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required to resize images.');
        }

        if (! is_readable($absolutePath)) {
            throw new InvalidArgumentException('Image file is not readable.');
        }

        $info = @getimagesize($absolutePath);
        if ($info === false) {
            throw new InvalidArgumentException('Uploaded file is not a valid image.');
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? 'application/octet-stream';

        $source = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            'image/gif' => @imagecreatefromgif($absolutePath),
            default => false,
        };

        if ($source === false) {
            throw new InvalidArgumentException('Unsupported or corrupt image type: '.$mime);
        }

        $scale = min(1.0, $maxEdge / max($width, $height));
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newW, $newH);
        if ($canvas === false) {
            imagedestroy($source);
            throw new RuntimeException('Could not allocate image canvas.');
        }

        // White background for formats with transparency when saving as JPEG.
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newW, $newH, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($source);

        $targetPath = preg_replace('/\.[^.]+$/', '', $absolutePath).'.jpg';
        if (! imagejpeg($canvas, $targetPath, $quality)) {
            imagedestroy($canvas);
            throw new RuntimeException('Failed to write resized JPEG.');
        }
        imagedestroy($canvas);

        if ($targetPath !== $absolutePath && is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        return [
            'width' => $newW,
            'height' => $newH,
            'mime' => 'image/jpeg',
            'path' => $targetPath,
        ];
    }
}
