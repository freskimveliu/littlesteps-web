<?php

declare(strict_types=1);

namespace App\Actions\Chapters;

use App\Data\ChapterData;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\User;

/**
 * A chapter the parent writes themselves — their own part of the story
 * alongside the guided one.
 *
 * It carries no xp: the ladder is paced by the catalogue, and a chapter anyone
 * can create and finish would be a way to mint levels rather than earn them.
 */
class CreateChapter
{
    public function handle(Child $child, User $author, ChapterData $data): ChildChapter
    {
        return $child->chapters()->create([
            ...$data->toAttributes(),
            'xp' => 0,
            'sort_order' => ($child->chapters()->max('sort_order') ?? 0) + 10,
            'is_editable' => true,
            'created_by_user_id' => $author->id,
        ]);
    }
}
