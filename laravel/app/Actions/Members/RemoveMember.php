<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Models\Child;
use App\Models\ChildMember;
use App\Models\User;

/**
 * The creator shows somebody the door, or somebody shows themselves out. Being
 * able to leave is what makes letting people in directly safe to offer.
 */
class RemoveMember
{
    public function handle(ChildMember $member, Child $child, User $actor): void
    {
        abort_if(
            $member->user_id === $child->created_by_user_id,
            403,
            'The parent who started the adventure stays in it.',
        );

        abort_unless(
            $member->user_id === $actor->id || $actor->can('share', $child),
            403,
            'Only the parent who started the adventure can remove somebody else.',
        );

        $member->delete();
    }
}
