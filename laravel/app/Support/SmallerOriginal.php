<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Throwable;

/**
 * Shrinks a photo before it is ever stored.
 *
 * The app already resizes at pick time, but only past 2000px and only when the
 * device obliges — a photo that arrives untouched is kept forever at whatever
 * size the camera chose. This is the floor under that: what we keep is never
 * bigger than what the largest conversion could want, re-encoded at a quality
 * nobody can see the difference in.
 *
 * Re-encoding drops the metadata with it, which is how the coordinates of the
 * room a baby was photographed in stop travelling along with the picture.
 *
 * A file the server cannot read — a HEIC, with no libheif behind GD — is left
 * exactly as it came. Same as its conversions: nothing, rather than something
 * wrong. See ChildEntry::registerMediaConversions.
 */
final class SmallerOriginal
{
    /** Comfortably above the 1600px `display` conversion, and well under a camera. */
    private const MAX_EDGE = 2000;

    private const QUALITY = 82;

    /** What GD can open and write back, and the extension it writes it as. */
    private const READABLE = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function of(UploadedFile $file): UploadedFile
    {
        $path = $file->getRealPath();
        $extension = self::READABLE[$file->getMimeType()] ?? null;

        if ($path === false || $extension === null) {
            return $file;
        }

        // An upload is a temp file with no extension, and the format written is
        // read off the name — so this goes out to a named neighbour and moves
        // back on top, rather than saving over itself.
        $named = $path.'.'.$extension;

        try {
            Image::load($path)
                ->fit(Fit::Max, self::MAX_EDGE, self::MAX_EDGE)
                ->quality(self::QUALITY)
                ->save($named);

            rename($named, $path);
        } catch (Throwable) {
            // A photo is worth more than the bytes it costs: keep the upload.
            @unlink($named);
        }

        return $file;
    }
}
