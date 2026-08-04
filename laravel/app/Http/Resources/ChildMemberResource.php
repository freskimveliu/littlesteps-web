<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Child;
use App\Models\ChildMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildMember */
class ChildMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $child = $request->route('child');
        $user = $request->user();

        $isOwner = $child instanceof Child && $this->user_id === $child->created_by_user_id;
        $isYou = $this->user_id === $user?->id;
        $mayManage = $child instanceof Child && $user?->can('share', $child) === true;

        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'relation' => $this->relation,
            'role' => $this->role,
            'canEdit' => $this->role->canEdit(),
            'isOwner' => $isOwner,
            'isYou' => $isYou,
            'abilities' => [
                'edit' => $mayManage && ! $isOwner,
                'remove' => ! $isOwner && ($mayManage || $isYou),
            ],
        ];
    }
}
