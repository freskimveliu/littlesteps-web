<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Data\MemberData;
use App\Models\Child;
use App\Models\ChildMember;

class UpdateMember
{
    public function handle(ChildMember $member, Child $child, MemberData $data): ChildMember
    {
        abort_if(
            $member->user_id === $child->created_by_user_id,
            403,
            'This is the parent who started the adventure.',
        );

        $member->update($data->toAttributes());

        return $member->fresh();
    }
}
