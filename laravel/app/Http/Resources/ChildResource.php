<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Child;
use App\Models\ChildMember;
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

        // Each member asks whose adventure this is, and the child is right here.
        // Handing it over saves a query per row — and is the only way the list
        // endpoints can answer at all, since they carry no {child} in the route.
        // Stripped of its own relations, or child and member would point at each
        // other and anything that later walked the graph would not come back.
        if ($this->relationLoaded('memberships')) {
            $owner = $this->resource->withoutRelations();
            $this->memberships->each(fn (ChildMember $member) => $member->setRelation('child', $owner));
        }

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
            // State describes the row; abilities grant an action. Same split as a
            // chapter and a milestone.
            'isOwner' => $user?->id === $this->created_by_user_id,
            'abilities' => $this->abilities($user),
            'members' => ChildMemberResource::collection($this->whenLoaded('memberships')),
        ];
    }
}
