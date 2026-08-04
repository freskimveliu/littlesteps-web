<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Data\MemberData;
use App\Models\Child;
use App\Models\ChildMember;
use App\Models\User;

/**
 * How somebody is related to the child is theirs to say, so anybody may set their
 * own. What they may do with the adventure is the creator's to grant, and the
 * creator's own place in it is not a grant at all — it cannot be handed anywhere.
 */
class UpdateMember
{
    public function handle(ChildMember $member, Child $child, MemberData $data, User $actor): ChildMember
    {
        $isMine = $member->user_id === $actor->id;
        $mayManage = $actor->can('share', $child);

        abort_unless(
            $isMine || $mayManage,
            403,
            'Only the parent who started the adventure can change somebody else.',
        );

        abort_if(
            $data->role !== null && (! $mayManage || $member->user_id === $child->created_by_user_id),
            403,
            'This is the parent who started the adventure.',
        );

        $member->update($data->toAttributes());

        return $member->fresh();
    }
}
