<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Models\ChildEntry;
use App\Support\Admin\ChildSummary;
use App\Support\Progress\Metrics;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ShowChildMemoriesController extends Controller
{
    public function __invoke(int $child, Metrics $metrics): Response
    {
        $record = ChildSummary::find($child);

        $entries = $record->entries()
            ->with([
                'milestone:id,name,child_chapter_id',
                'milestone.chapter:id,name',
                'creator:id,name,email',
                'properties',
                'media',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->each->bindMediaOwner()
            ->map(fn (ChildEntry $entry) => [
                'id' => $entry->id,
                'milestone' => $entry->milestone?->name,
                'chapter' => $entry->milestone?->chapter?->name,
                'description' => $entry->description,
                'date' => $entry->date->toDateString(),
                'mood' => $entry->mood,
                'is_free' => $entry->isFree(),
                'author' => $entry->creator?->only(['id', 'name', 'email']),
                'media' => $entry->getMedia(ChildEntry::MEDIA)->map(fn (Media $media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'mime' => $media->mime_type,
                    'size' => $media->size,
                    'thumb' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                    'display' => $media->hasGeneratedConversion('display') ? $media->getUrl('display') : $media->getUrl(),
                    'original' => $media->getUrl(),
                ])->values(),
                'properties' => $entry->properties->map(fn ($p) => [
                    'label' => $p->name ?? ucfirst($p->key->value),
                    'value' => $p->value,
                    'unit' => $p->key->unit(),
                ]),
                'created_at' => $entry->created_at?->toIso8601String(),
                'updated_at' => $entry->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Children/Show/Memories', [
            ...ChildSummary::for($record, $metrics->for($record)),
            'entries' => $entries,
        ]);
    }
}
