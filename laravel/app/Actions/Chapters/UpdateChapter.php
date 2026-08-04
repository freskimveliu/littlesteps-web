<?php

declare(strict_types=1);

namespace App\Actions\Chapters;

use App\Data\ChapterData;
use App\Models\ChildChapter;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateChapter
{
    public function handle(ChildChapter $chapter, User $editor, ChapterData $data): ChildChapter
    {
        // The age is what makes a guided chapter that chapter — move it and every
        // milestone underneath it unlocks against a month it was never written for.
        if ($chapter->isGuided() && $data->sent('months_from')) {
            throw ValidationException::withMessages([
                'months_from' => 'This chapter belongs to the age it describes and cannot be moved in time.',
            ]);
        }

        $chapter->update([...$data->toAttributes(), 'updated_by_user_id' => $editor->id]);

        return $chapter->fresh();
    }
}
