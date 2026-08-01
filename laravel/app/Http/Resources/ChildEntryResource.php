<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChildEntry */
class ChildEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'childId' => $this->child_id,
            'stepId' => $this->child_milestone_id,
            'name' => $this->whenLoaded('milestone', fn () => $this->milestone?->name),
            'description' => $this->description,
            'date' => $this->date->toDateString(),
            'mood' => $this->mood,
            'isFree' => $this->isFree(),
            'isEditable' => true,
            'isDeletable' => $this->isDeletable(),
            'properties' => ChildEntryPropertyResource::collection($this->whenLoaded('properties')),
            'photos' => $this->getMedia(ChildEntry::PHOTOS)->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'display' => $media->getUrl('display'),
            ])->values(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
