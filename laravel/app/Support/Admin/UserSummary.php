<?php

declare(strict_types=1);

namespace App\Support\Admin;

use App\Models\ChildEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The header every user page shares: who the account is and the five counters
 * above the tabs.
 */
class UserSummary
{
    /** @return array<string, mixed> */
    public static function for(User $parent): array
    {
        return [
            'id' => $parent->id,
            'name' => $parent->name,
            'email' => $parent->email,
            'share_code' => $parent->share_code,
            'timezone' => $parent->timezone,
            'language' => $parent->language,
            'is_admin' => $parent->is_admin,
            'is_registered' => $parent->isRegistered(),
            'current_streak' => $parent->current_streak,
            'longest_streak' => $parent->longest_streak,
            'last_entry_date' => $parent->last_entry_date?->toDateString(),
            'deleted_at' => $parent->deleted_at?->toIso8601String(),
            'created_at' => $parent->created_at?->toIso8601String(),
            'photo' => $parent->photoThumbUrl(),
            'children_count' => $parent->children()->count(),
            'devices_count' => $parent->devices()->count(),
            'written' => ChildEntry::where('created_by_user_id', $parent->id)->count(),
            'photos' => self::photos($parent),
        ];
    }

    public static function find(int $id): User
    {
        return User::withTrashed()->findOrFail($id);
    }

    private static function photos(User $parent): int
    {
        return DB::table('media')
            ->where('model_type', ChildEntry::class)
            ->where('collection_name', ChildEntry::MEDIA)
            ->where('mime_type', 'like', 'image/%')
            ->whereIn('model_id', ChildEntry::where('created_by_user_id', $parent->id)->select('id'))
            ->count();
    }
}
