<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\KnowsWhoIsAsking;
use App\Models\ChildEntry;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin ChildEntry */
class ChildEntryResource extends JsonResource
{
    use KnowsWhoIsAsking;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $mayWrite = $this->mayWrite($request);

        return [
            'id' => $this->id,
            'childId' => $this->child_id,
            'milestoneId' => $this->child_milestone_id,
            'name' => $this->whenLoaded('milestone', fn () => $this->milestone?->name),
            'description' => $this->description,
            'date' => $this->date->toDateString(),
            'mood' => $this->mood,
            'isFree' => $this->isFree(),
            'abilities' => $this->abilities($mayWrite),
            'properties' => ChildEntryPropertyResource::collection($this->whenLoaded('properties')),
            'media' => $this->getMedia(ChildEntry::MEDIA)->map(fn (Media $media) => [
                'id' => $media->id,
                'type' => $media->type,
                'mimeType' => $media->mime_type,
                'url' => MediaUrl::for($media),
                'thumb' => $media->hasGeneratedConversion('thumb') ? MediaUrl::for($media, 'thumb') : null,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
            ])->values(),
            'createdBy' => new AuthorResource($this->whenLoaded('creator')),
            'updatedBy' => new AuthorResource($this->whenLoaded('editor')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
