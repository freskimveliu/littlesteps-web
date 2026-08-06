<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;

final class Dimensions
{
    /** @return array{width?: int, height?: int} */
    public static function of(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        return $path === false ? [] : self::read(fn () => @getimagesize($path));
    }

    /** @return array{width?: int, height?: int} */
    public static function ofBytes(string $contents): array
    {
        return self::read(fn () => @getimagesizefromstring($contents));
    }

    /**
     * @param  callable(): (array<int, mixed>|false)  $measure
     * @return array{width?: int, height?: int}
     */
    private static function read(callable $measure): array
    {
        $size = $measure();

        if ($size === false || $size[0] <= 0 || $size[1] <= 0) {
            return [];
        }

        return ['width' => (int) $size[0], 'height' => (int) $size[1]];
    }
}
