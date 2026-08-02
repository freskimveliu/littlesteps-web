<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The one place that turns a stored file into something a phone can load.
 *
 * Media on a private disk is never handed out directly. What goes in a payload
 * is a link to our own /media/{uuid} route, which redirects to a signed link
 * that expires. The uuid is the only key to the file, so a photo travels no
 * further than the payload it came in — and revoking it is deleting the row.
 *
 * Media on a disk the world can already read (catalogue artwork on `public`)
 * skips the detour and is handed out as-is.
 */
final class MediaUrl
{
    /** How long a signed link keeps working once it has been handed out. */
    private const LIFETIME_HOURS = 12;

    /**
     * How long we hand out the *same* signed link. Shorter than the lifetime,
     * so a link is always young when it leaves here — and stable long enough
     * that a phone can keep the picture it already downloaded.
     */
    private const REUSE_HOURS = 6;

    public static function for(Media $media, ?string $conversion = null): string
    {
        $conversion = self::available($media, $conversion);

        if (self::isWorldReadable($media)) {
            return self::direct($media, $conversion);
        }

        return route('media.show', array_filter([
            'uuid' => $media->uuid,
            'conversion' => $conversion,
        ]));
    }

    /** The link the /media/{uuid} route sends a caller on to. */
    public static function target(Media $media, ?string $conversion = null): string
    {
        $conversion = self::available($media, $conversion);

        if (self::isWorldReadable($media) || ! Storage::disk($media->disk)->providesTemporaryUrls()) {
            return self::direct($media, $conversion);
        }

        $key = "media-url:{$media->uuid}:".($conversion ?? 'original');

        return Cache::remember(
            $key,
            now()->addHours(self::REUSE_HOURS),
            fn () => $media->getTemporaryUrl(
                now()->addHours(self::LIFETIME_HOURS),
                $conversion ?? '',
            ),
        );
    }

    /** A conversion that was never made falls back to the original. */
    private static function available(Media $media, ?string $conversion): ?string
    {
        if ($conversion === null || ! $media->hasGeneratedConversion($conversion)) {
            return null;
        }

        return $conversion;
    }

    /**
     * A disk is only open to the world if it says so. `public` does; `s3` is
     * configured without a visibility, so nothing on it is reachable without
     * a signature — which is the whole reason the route exists.
     */
    private static function isWorldReadable(Media $media): bool
    {
        return config("filesystems.disks.{$media->disk}.visibility") === 'public';
    }

    private static function direct(Media $media, ?string $conversion): string
    {
        return $conversion === null ? $media->getUrl() : $media->getUrl($conversion);
    }
}
