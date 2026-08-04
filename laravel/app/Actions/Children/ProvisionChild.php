<?php

declare(strict_types=1);

namespace App\Actions\Children;

use App\Models\Chapter;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\ChildMilestone;
use Illuminate\Support\Facades\DB;

/**
 * Copies the whole catalogue onto a new child — around 170 rows in one
 * transaction, which is what the app's preparing screen is covering.
 *
 * Everything is copied rather than joined, so an admin rewording a milestone later
 * never rewrites what a parent already saved. template_*_id is kept only so a
 * deliberate backfill of untouched rows stays possible.
 */
class ProvisionChild
{
    public function handle(Child $child): void
    {
        $chapters = Chapter::query()
            ->active()
            ->with(['milestones' => fn ($q) => $q->active()->with('properties')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        DB::transaction(function () use ($child, $chapters) {
            foreach ($chapters as $chapter) {
                $childChapter = ChildChapter::create([
                    'child_id' => $child->id,
                    'chapter_id' => $chapter->id,
                    'name' => $chapter->name,
                    'description' => $chapter->description,
                    'icon' => $chapter->icon,
                    'months_from' => $chapter->months_from,
                    'xp' => $chapter->xp,
                    'sort_order' => $chapter->sort_order,
                    'is_editable' => $chapter->is_editable,
                    'created_by_user_id' => $child->created_by_user_id,
                ]);

                foreach ($chapter->milestones as $milestone) {
                    $childMilestone = ChildMilestone::create([
                        'child_id' => $child->id,
                        'child_chapter_id' => $childChapter->id,
                        'milestone_id' => $milestone->id,
                        'category_id' => $milestone->category_id,
                        'name' => $milestone->name,
                        'icon' => $milestone->icon,
                        'months_from' => $milestone->months_from,
                        'is_dated' => $milestone->is_dated,
                        'xp' => $milestone->xp,
                        'sort_order' => $milestone->sort_order,
                        'is_editable' => $milestone->is_editable,
                        'created_by_user_id' => $child->created_by_user_id,
                    ]);

                    foreach ($milestone->properties as $property) {
                        $childMilestone->properties()->create([
                            'key' => $property->key,
                            'name' => $property->name,
                            'sort_order' => $property->sort_order,
                        ]);
                    }
                }
            }
        });
    }
}
