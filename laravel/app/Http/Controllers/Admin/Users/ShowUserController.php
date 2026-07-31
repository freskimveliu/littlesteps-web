<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Models\ChildEntry;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserController extends Controller
{
    public function __invoke(int $user): Response
    {
        $parent = User::withTrashed()
            ->with([
                'children' => fn ($q) => $q->withCount('entries')->orderBy('birthday'),
                'devices',
                'settings',
            ])
            ->findOrFail($user);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $parent->id,
                'name' => $parent->name,
                'email' => $parent->email,
                'language' => $parent->language,
                'timezone' => $parent->timezone,
                'is_admin' => $parent->is_admin,
                'is_registered' => $parent->isRegistered(),
                'current_streak' => $parent->current_streak,
                'longest_streak' => $parent->longest_streak,
                'last_entry_date' => $parent->last_entry_date?->toDateString(),
                'deleted_at' => $parent->deleted_at?->toIso8601String(),
                'created_at' => $parent->created_at?->toIso8601String(),
                'photo' => $parent->photoThumbUrl(),
                'settings' => $parent->settingsMap(),
            ],
            'children' => $parent->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'birthday' => $child->birthday->toDateString(),
                'age_months' => $child->ageInMonths(),
                'gender' => $child->gender,
                'xp' => $child->xp,
                'entries_count' => $child->entries_count,
                'is_owner' => $child->created_by_user_id === $parent->id,
                'role' => $child->pivot->role,
                'relation' => $child->pivot->relation,
            ]),
            'devices' => $parent->devices,
            'written' => ChildEntry::where('created_by_user_id', $parent->id)->count(),
            'languages' => array_column(Language::cases(), 'value'),
        ]);
    }
}
