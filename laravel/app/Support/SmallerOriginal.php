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
 * No camera-size original is kept. What we store is the biggest thing anything
 * asks for — a full-screen photo on a 3x phone — and the conversions below it
 * are smaller again. A photo that arrives untouched is otherwise kept forever
 * at whatever size the camera chose.
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
    /** A full-screen photo on a 3x phone, and nothing beyond it. */
    private const MAX_EDGE = 1200;

    private const QUALITY = 78;

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
                ->optimize()
                ->save($named);

            rename($named, $path);
        } catch (Throwable) {
            // A photo is worth more than the bytes it costs: keep the upload.
            @unlink($named);
        }

        return $file;
    }

    public static function ofBytes(string $contents, ?string $mimeType): ?string
    {
        $extension = self::READABLE[$mimeType] ?? null;

        if ($extension === null) {
            return null;
        }

        $stem = tempnam(sys_get_temp_dir(), 'shrink');

        if ($stem === false) {
            return null;
        }

        $source = $stem.'.'.$extension;
        $shrunk = $stem.'.smaller.'.$extension;

        try {
            file_put_contents($source, $contents);

            Image::load($source)
                ->fit(Fit::Max, self::MAX_EDGE, self::MAX_EDGE)
                ->quality(self::QUALITY)
                ->optimize()
                ->save($shrunk);

            $bytes = file_get_contents($shrunk);

            return $bytes === false ? null : $bytes;
        } catch (Throwable) {
            return null;
        } finally {
            @unlink($stem);
            @unlink($source);
            @unlink($shrunk);
        }
    }
}
