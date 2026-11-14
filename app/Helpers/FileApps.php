<?php

namespace App\Helpers;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Image uploads for master data (department illustrations, landing hero).
 *
 * Uploads are decoded and re-encoded with GD, which drops metadata (EXIF,
 * GPS) and any non-image payload, downscaled to a web friendly size and
 * stored under a random name on the public disk (served through
 * "php artisan storage:link").
 */
class FileApps
{
    /**
     * Disk holding uploaded images.
     */
    public const DISK = 'public';

    /**
     * Largest accepted width / height in pixels (covers phone photos).
     */
    public const MAX_DIMENSION = 6000;

    /**
     * Longest side of the stored image in pixels.
     */
    public const MAX_STORED_DIMENSION = 1920;

    /**
     * Approximate memory GD needs per pixel of a true colour image.
     */
    protected const BYTES_PER_PIXEL = 5;

    /**
     * Validation rules for an optional image upload (SVG is never accepted).
     *
     * @return array<int, string>
     */
    public static function imageRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
            'dimensions:max_width='.self::MAX_DIMENSION.',max_height='.self::MAX_DIMENSION,
        ];
    }

    /**
     * Store an uploaded image and return its path on the public disk.
     *
     * @throws ValidationException
     */
    public static function storeImage(UploadedFile $file, string $directory, string $attribute = 'image'): string
    {
        $directory = trim($directory, '/');

        if (! function_exists('imagewebp')) {
            return $file->store($directory, self::DISK);
        }

        $size = @getimagesize($file->getRealPath());

        if ($size === false) {
            throw ValidationException::withMessages([$attribute => 'The image could not be processed.']);
        }

        if (! static::fitsInMemory($size[0], $size[1])) {
            throw ValidationException::withMessages([
                $attribute => 'The image is too large to process. Please resize it to at most '.self::MAX_STORED_DIMENSION.' pixels and try again.',
            ]);
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw ValidationException::withMessages([$attribute => 'The image could not be processed.']);
        }

        imagepalettetotruecolor($image);
        $image = static::downscale($image, self::MAX_STORED_DIMENSION);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, 85);
        $binary = (string) ob_get_clean();

        $path = $directory.'/'.Str::random(40).'.webp';
        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }

    /**
     * Shrink an image so its longest side is at most the given size.
     */
    protected static function downscale(GdImage $image, int $maxDimension): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longestSide = max($width, $height);

        if ($longestSide <= $maxDimension) {
            return $image;
        }

        $ratio = $maxDimension / $longestSide;
        $scaled = imagescale($image, max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)), IMG_BICUBIC);

        return $scaled === false ? $image : $scaled;
    }

    /**
     * Whether GD can decode (and downscale) an image of this size within the
     * PHP memory limit, so a huge image fails validation instead of the request.
     */
    protected static function fitsInMemory(int $width, int $height): bool
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));

        if ($limit <= 0) {
            return true;
        }

        $needed = ($width * $height + self::MAX_STORED_DIMENSION ** 2) * self::BYTES_PER_PIXEL;

        return memory_get_usage() + $needed < $limit;
    }

    /**
     * Apply an upload / removal to a stored image and return the new path.
     *
     * @throws ValidationException
     */
    public static function replaceImage(?string $currentPath, ?UploadedFile $upload, bool $remove, string $directory, string $attribute = 'image'): ?string
    {
        if ($upload !== null) {
            $newPath = static::storeImage($upload, $directory, $attribute);
            static::delete($currentPath);

            return $newPath;
        }

        if ($remove) {
            static::delete($currentPath);

            return null;
        }

        return $currentPath;
    }

    /**
     * Delete a stored file (ignores empty paths).
     */
    public static function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Public URL of a stored file, relative to the current host.
     */
    public static function url(?string $path): ?string
    {
        return filled($path) ? asset('storage/'.ltrim((string) $path, '/')) : null;
    }
}
