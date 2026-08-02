<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Support\Admin\UserSummary;
use App\Support\Progress\LevelLadder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserChildrenController extends Controller
{
    public function __invoke(int $user): Response
    {
        $parent = UserSummary::find($user);

        $writtenPerChild = ChildEntry::where('created_by_user_id', $parent->id)
            ->select('child_id', DB::raw('count(*) as total'))
            ->groupBy('child_id')
            ->pluck('total', 'child_id');

        $children = $parent->children()
            ->withCount([
                'entries',
                'trophies',
                'chapters as chapters_done_count' => fn ($c) => $c->whereNotNull('completed_at'),
            ])
            ->orderBy('birthday')
            ->get();

        return Inertia::render('Admin/Users/Children', [
            'user' => UserSummary::for($parent),
            'chapterCount' => Chapter::count(),
            'children' => $children->map(function (Child $child) use ($parent, $writtenPerChild) {
                $level = LevelLadder::for($child->xp);

                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'birthday' => $child->birthday->toDateString(),
                    'age_months' => $child->ageInMonths(),
                    'gender' => $child->gender,
                    'xp' => $child->xp,
                    'photo' => $child->photoThumbUrl(),
                    'level' => $level['level'],
                    'level_name' => $level['name'],
                    'level_progress' => $level['progress'],
                    'xp_to_next' => $level['xp_to_next'],
                    'entries_count' => $child->entries_count,
                    'trophies_count' => $child->trophies_count,
                    'chapters_done_count' => $child->chapters_done_count,
                    'written_here' => (int) $writtenPerChild->get($child->id, 0),
                    'is_owner' => $child->created_by_user_id === $parent->id,
                    'role' => $child->pivot->role,
                    'relation' => $child->pivot->relation,
                ];
            }),
        ]);
    }
}
