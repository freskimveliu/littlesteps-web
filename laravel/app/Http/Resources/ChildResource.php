<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Child;
use App\Support\Progress\LevelLadder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Child */
class ChildResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'birthday' => $this->birthday->toDateString(),
            'ageMonths' => $this->ageInMonths(),
            'gender' => $this->gender,
            'xp' => $this->xp,
            'level' => LevelLadder::for($this->xp),
            'levelCount' => LevelLadder::total(),
            'photo' => $this->photoUrl() ? [
                'url' => $this->photoUrl(),
                'thumb' => $this->photoThumbUrl(),
            ] : null,
            'isOwner' => $user?->id === $this->created_by_user_id,
            'isEditable' => $user?->can('update', $this->resource) ?? false,
            'isDeletable' => $user?->can('delete', $this->resource) ?? false,
            'canContribute' => $user?->can('contribute', $this->resource) ?? false,
            'members' => ChildMemberResource::collection($this->whenLoaded('memberships')),
        ];
    }
}
