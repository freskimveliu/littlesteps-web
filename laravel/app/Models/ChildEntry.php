<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'child_id', 'child_milestone_id', 'description', 'date', 'mood',
    'created_by_user_id', 'updated_by_user_id',
])]
class ChildEntry extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const PHOTOS = 'photos';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mood' => Mood::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/heic']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 320, 320)
            ->format('webp')
            ->quality(80)
            ->optimize()
            ->nonQueued();

        $this->addMediaConversion('display')
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp')
            ->quality(82)
            ->optimize()
            ->nonQueued();
    }

    public function getAttribute($key)
    {
        if ($key === 'user_id') {
            return $this->created_by_user_id;
        }

        return parent::getAttribute($key);
    }

    public function isFree(): bool
    {
        return $this->child_milestone_id === null;
    }

    /**
     * A memory attached to a milestone is permanent — the XP is already awarded and
     * the milestone has no other way of being un-recorded. A free one can go.
     */
    public function isDeletable(): bool
    {
        return $this->isFree();
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<ChildMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ChildMilestone::class, 'child_milestone_id');
    }

    /** @return HasMany<ChildEntryProperty, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(ChildEntryProperty::class);
    }

    /** @param Builder<$this> $query */
    public function scopeFree(Builder $query): void
    {
        $query->whereNull('child_milestone_id');
    }
}
