<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\User;
use App\Support\Media\UserScopedPathGenerator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Deletes an account for good: the children it started, every chapter,
 * milestone, memory and photo under them, and finally the row itself. Nothing
 * is left soft-deleted — withTrashed() finds nothing afterwards either.
 *
 * It runs off the queue because a family album is a lot of files and none of
 * it is anything the parent should be made to wait through — every token is
 * already revoked by the time this starts, so nothing can reach the account
 * while it empties.
 *
 * Two things deliberately survive:
 *
 * - Children shared *with* this account. They belong to whoever started them;
 *   only the membership goes, which the foreign key does on its own.
 * - Memories this parent added to somebody else's child. Leaving an adventure
 *   has never taken your memories out of it, and closing an account is leaving
 *   every adventure at once. Their photos move to the owning family's folder
 *   first, because the folder they are in is about to have no account.
 */
class PurgeUserAccount implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $userId) {}

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function handle(): void
    {
        // withTrashed because the console closes accounts by soft-deleting them,
        // and erasing one from there comes through here too.
        $user = User::withTrashed()->find($this->userId);

        if (! $user) {
            return;
        }

        $this->handOverContributedMedia($user);
        $this->deleteOwnedChildren($user);

        $user->clearMediaCollection(User::PHOTO);
        $this->sweepFolder();

        $user->settings()->delete();
        $user->devices()->delete();
        $user->tokens()->delete();

        $user->forceDelete();
    }

    /**
     * Everything is filed under users/{id}, so once the models have taken their
     * own files off the disk the account's folder goes whole. Nothing of anyone
     * else's is in it by this point — what was has already been handed over —
     * and this is what catches a conversion or a responsive image whose media
     * row went missing at some point and left the file behind.
     */
    private function sweepFolder(): void
    {
        $disks = array_unique([
            config('media-library.disk_name'),
            config('media-library.conversions_disk_name') ?: config('media-library.disk_name'),
        ]);

        foreach ($disks as $disk) {
            Storage::disk($disk)->deleteDirectory("users/{$this->userId}");
        }
    }

    /**
     * Model by model rather than leaving it to the cascade: a child the database
     * deletes never tells its files to go, and the rows in the media table know
     * nothing about the foreign keys either. The folder sweep is the safety net,
     * not the plan.
     */
    private function deleteOwnedChildren(User $user): void
    {
        $user->ownedChildren()->each(function (Child $child): void {
            $child->entries()->with('media')->each(
                fn (ChildEntry $entry) => $entry->delete(),
            );

            $child->delete();
        });
    }

    /**
     * A memory left in another family's album keeps its photos, so they move to
     * that family's folder — where ChildEntry::childOwnerId() will look for them
     * once the foreign key has nulled the author this path was spelled from.
     */
    private function handOverContributedMedia(User $user): void
    {
        ChildEntry::query()
            ->where('created_by_user_id', $user->id)
            ->whereHas('child', fn ($child) => $child->whereNot('created_by_user_id', $user->id))
            ->with(['media', 'child'])
            ->each(function (ChildEntry $entry): void {
                foreach ($entry->getMedia(ChildEntry::MEDIA) as $media) {
                    $this->handOver($media, (int) $entry->child->created_by_user_id);
                }
            });
    }

    private function handOver(Media $media, int $owner): void
    {
        $from = UserScopedPathGenerator::directory($media, $this->userId);
        $into = UserScopedPathGenerator::directory($media, $owner);

        if ($from === $into) {
            return;
        }

        foreach (array_filter(array_unique([$media->disk, $media->conversions_disk])) as $disk) {
            $storage = Storage::disk($disk);

            foreach ($storage->allFiles($from) as $file) {
                $storage->move($file, $into.Str::after($file, $from));
            }

            $storage->deleteDirectory($from);
        }
    }
}
