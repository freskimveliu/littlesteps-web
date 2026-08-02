<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Hands a caller on to the file itself.
 *
 * No token is asked for: the uuid in the path is the credential, which is what
 * lets an <Image> tag load a private photo without carrying a header. The link
 * we redirect to is signed and short-lived, so it cannot be passed around.
 */
class ShowMediaController extends Controller
{
    public function __invoke(string $uuid, ?string $conversion = null): RedirectResponse
    {
        $media = Media::where('uuid', $uuid)->firstOrFail();

        return redirect()
            ->away(MediaUrl::target($media, $conversion))
            // The same uuid keeps pointing at the same file, so a phone may
            // hold on to the answer — for less time than the link stays good.
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
