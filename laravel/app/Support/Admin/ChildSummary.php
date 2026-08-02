<?php

declare(strict_types=1);

namespace App\Support\Admin;

use App\Models\Child;
use App\Support\Progress\LevelLadder;

/**
 * The header every child page shares: who the child is, where they are on the
 * ladder, and the counts the tabs are labelled with.
 */
class ChildSummary
{
    public static function find(int $id): Child
    {
        return Child::with('creator:id,name,email')->findOrFail($id);
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, mixed>
     */
    public static function for(Child $child, array $counts): array
    {
        return [
            'summary' => [
                'child' => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'birthday' => $child->birthday->toDateString(),
                    'age_months' => $child->ageInMonths(),
                    'gender' => $child->gender,
                    'xp' => $child->xp,
                    'photo' => $child->photoThumbUrl(),
                    'created_at' => $child->created_at?->toIso8601String(),
                    'creator' => $child->creator?->only(['id', 'name', 'email']),
                ],
                'level' => LevelLadder::for($child->xp),
                'levelCount' => LevelLadder::total(),
                'metrics' => $counts,
                'entriesTotal' => $child->entries()->count(),
                'rewardsCount' => $child->rewards()->count(),
                'membersCount' => $child->memberships()->count(),
            ],
        ];
    }
}
