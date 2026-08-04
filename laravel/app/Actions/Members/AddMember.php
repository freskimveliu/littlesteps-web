<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Data\MemberData;
use App\Models\Child;
use App\Models\ChildMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The code belongs to the person being let in, never to the child: the parent is
 * told it over the phone and types it here, so the door only opens from inside.
 */
class AddMember
{
    public function handle(Child $child, MemberData $data): ChildMember
    {
        $user = User::where('share_code', $data->shareCode)->first();

        // One answer for every way of failing, so this cannot be used to find out
        // which codes exist.
        if (! $user || $user->id === $child->created_by_user_id) {
            throw ValidationException::withMessages([
                'share_code' => 'That code does not belong to anybody.',
            ]);
        }

        if ($child->memberships()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'share_code' => "{$user->name} is already part of this adventure.",
            ]);
        }

        return $child->memberships()->create([
            'user_id' => $user->id,
            ...$data->toAttributes(),
        ]);
    }
}
