<?php

declare(strict_types=1);

namespace App\Actions\Children;

use App\Models\Child;
use App\Models\ChildMilestone;
use App\Models\ChildStep;
use App\Models\TemplateMilestone;
use Illuminate\Support\Facades\DB;

/**
 * Copies the whole catalogue onto a new child — around 170 rows in one
 * transaction, which is what the app's preparing screen is covering.
 *
 * Everything is copied rather than joined, so an admin rewording a step later
 * never rewrites what a parent already saved. template_*_id is kept only so a
 * deliberate backfill of untouched rows stays possible.
 */
class ProvisionChild
{
    public function handle(Child $child): void
    {
        $chapters = TemplateMilestone::query()
            ->active()
            ->with(['steps' => fn ($q) => $q->active()->with('properties')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        DB::transaction(function () use ($child, $chapters) {
            foreach ($chapters as $chapter) {
                $childChapter = ChildMilestone::create([
                    'child_id' => $child->id,
                    'template_milestone_id' => $chapter->id,
                    'name' => $chapter->name,
                    'description' => $chapter->description,
                    'icon' => $chapter->icon,
                    'months_from' => $chapter->months_from,
                    'xp' => $chapter->xp,
                    'sort_order' => $chapter->sort_order,
                    'is_editable' => $chapter->is_editable,
                    'created_by_user_id' => $child->created_by_user_id,
                ]);

                foreach ($chapter->steps as $step) {
                    $childStep = ChildStep::create([
                        'child_id' => $child->id,
                        'child_milestone_id' => $childChapter->id,
                        'template_step_id' => $step->id,
                        'category_id' => $step->category_id,
                        'name' => $step->name,
                        'icon' => $step->icon,
                        'months_from' => $step->months_from,
                        'xp' => $step->xp,
                        'sort_order' => $step->sort_order,
                        'is_editable' => $step->is_editable,
                        'created_by_user_id' => $child->created_by_user_id,
                    ]);

                    foreach ($step->properties as $property) {
                        $childStep->properties()->create([
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
