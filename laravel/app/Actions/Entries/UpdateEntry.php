<?php

declare(strict_types=1);

namespace App\Actions\Entries;

use App\Actions\Progress\EvaluateTrophies;
use App\Data\EntryChangeData;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\User;
use App\Support\SmallerOriginal;
use Illuminate\Support\Facades\DB;

/**
 * Editing is the only correction path for a memory attached to a milestone, so it
 * has to cover everything: the date, the words, the mood and the measurements.
 *
 * The one exception is a memory filed against a dated milestone. "Month 5" and
 * "Fourth Birthday" are days the calendar fixed, not days the parent chose, so the
 * date is theirs to see and not to change.
 */
class UpdateEntry
{
    public function __construct(private readonly EvaluateTrophies $trophies) {}

    public function handle(ChildEntry $entry, Child $child, User $editor, EntryChangeData $data): ChildEntry
    {
        $attributes = $data->toAttributes();

        // dateFor(), not isDated(): a dated milestone with no age names no
        // particular day, so there is nothing fixed and the date stays the parent's.
        if ($entry->milestone?->dateFor($child) !== null) {
            unset($attributes['date']);
        }

        DB::transaction(function () use ($entry, $editor, $attributes, $data) {
            $entry->update([...$attributes, 'updated_by_user_id' => $editor->id]);

            if ($data->properties !== null) {
                $entry->properties()->delete();

                foreach ($data->properties as $i => $property) {
                    $entry->properties()->create([...$property, 'sort_order' => ($i + 1) * 10]);
                }
            }

            foreach ($data->media as $file) {
                $entry->addMedia(SmallerOriginal::of($file))->toMediaCollection(ChildEntry::MEDIA);
            }
        });

        // An edit can add the photo, or move the date into the window, that finally
        // carries a rule over the line. Nothing celebrates it — the Awards screen
        // simply has it unlocked next time it is read.
        $this->trophies->handle($child);

        return $entry->fresh()->load(['milestone', 'properties', 'media', 'creator', 'editor'])->bindMediaOwner();
    }
}
