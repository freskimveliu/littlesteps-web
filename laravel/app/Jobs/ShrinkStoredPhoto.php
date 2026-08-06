<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Dimensions;
use App\Support\SmallerOriginal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ShrinkStoredPhoto implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $mediaId) {}

    public function handle(): void
    {
        $photo = Media::query()->find($this->mediaId);

        if ($photo === null) {
            return;
        }

        $disk = Storage::disk($photo->disk);
        $path = $photo->getPathRelativeToRoot();
        $stored = $disk->get($path);

        if ($stored === null) {
            return;
        }

        $smaller = SmallerOriginal::ofBytes($stored, $photo->mime_type);

        if ($smaller === null) {
            return;
        }

        $disk->put($path, $smaller);

        $photo->size = strlen($smaller);

        foreach (Dimensions::ofBytes($smaller) as $side => $pixels) {
            $photo->setCustomProperty($side, $pixels);
        }

        $photo->save();
    }
}
