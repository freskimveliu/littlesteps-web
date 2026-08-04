<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\ChildEntry;
use Illuminate\Validation\ValidationException;

/**
 * An attachment goes unless it is the last thing the memory has to show for
 * itself — a memory with no words and no picture is an empty row in the story.
 */
class DeleteEntryMedia
{
    public function handle(ChildEntry $entry, int $mediaId): void
    {
        $file = $entry->getMedia(ChildEntry::MEDIA)->firstWhere('id', $mediaId);

        abort_unless($file, 404);

        if ($entry->mediaCount() === 1 && blank($entry->description)) {
            throw ValidationException::withMessages([
                'file' => 'This memory has no words yet — add some before removing the last of it.',
            ]);
        }

        $file->delete();
    }
}
