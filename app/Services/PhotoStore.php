<?php

namespace App\Services;

use App\Exceptions\InvalidImage;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Kompres foto bukti: auto-orient EXIF, sisi terpanjang max 1280px, WebP q75, disk privat. */
final class PhotoStore
{
    private const MAX_SIDE = 1280;

    private const QUALITY = 75;

    /** @throws InvalidImage */
    public function store(UploadedFile $file): string
    {
        $im = $this->decode($file);
        $im = $this->orient($im, $file);
        $im = $this->shrink($im);

        ob_start();
        imagewebp($im, null, self::QUALITY);
        $webp = ob_get_clean();

        $path = 'photos/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        Storage::disk('local')->put($path, $webp);

        return $path;
    }

    public function delete(string $path): void
    {
        Storage::disk('local')->delete($path);
    }

    private function decode(UploadedFile $file): GdImage
    {
        $bytes = @file_get_contents($file->getRealPath());
        $info = $bytes === false ? false : @getimagesizefromstring($bytes);

        // GD memuat bitmap penuh ~5 byte/piksel; tolak yang melebihi sisa memori agar tidak fatal.
        if (! $info || $info[0] * $info[1] * 5 > $this->memoryBudget()) {
            throw new InvalidImage;
        }

        return @imagecreatefromstring($bytes) ?: throw new InvalidImage;
    }

    private function orient(GdImage $im, UploadedFile $file): GdImage
    {
        $exif = $file->getMimeType() === 'image/jpeg' ? @exif_read_data($file->getRealPath()) : false;
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle ? (imagerotate($im, $angle, 0) ?: $im) : $im;
    }

    private function shrink(GdImage $im): GdImage
    {
        $w = imagesx($im);
        $h = imagesy($im);

        if (max($w, $h) <= self::MAX_SIDE) {
            return $im;
        }

        return imagescale($im, $w >= $h ? self::MAX_SIDE : (int) round($w * self::MAX_SIDE / $h), $h > $w ? self::MAX_SIDE : (int) round($h * self::MAX_SIDE / $w)) ?: $im;
    }

    private function memoryBudget(): int
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));

        return $limit <= 0 ? PHP_INT_MAX : (int) (($limit - memory_get_usage()) * 0.8);
    }
}
