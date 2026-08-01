<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChildEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
            'media' => $this->getMedia(ChildEntry::MEDIA)->map(fn (Media $media) => [
                'id' => $media->id,
                'type' => $media->type,
                'mimeType' => $media->mime_type,
                'url' => $media->getUrl(),
                'thumb' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
                'display' => $media->hasGeneratedConversion('display') ? $media->getUrl('display') : null,
            ])->values(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
