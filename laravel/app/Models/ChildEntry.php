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

    public const MEDIA = 'media';

    /**
     * What a memory is allowed to carry. Images are all a parent can attach today, but
     * nothing above this list knows that — widen it here and the collection, the upload
     * rules and the API move together.
     */
    public const ACCEPTS = ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mood' => Mood::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA)
            ->acceptsMimeTypes(self::ACCEPTS);
    }

    /**
     * Queued, unlike the single-photo collections elsewhere. A memory can carry
     * several full-size photos at once, and resizing them all before answering is
     * what a parent was waiting through on the Saving button.
     *
     * Nothing downstream needs them to exist yet: ChildEntryResource sends null
     * for a conversion that has not been generated, and the app falls back to the
     * original until it has.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 320, 320)
            ->format('webp')
            ->quality(80)
            ->optimize();

        $this->addMediaConversion('display')
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp')
            ->quality(82)
            ->optimize();
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

    public function mediaCount(): int
    {
        return $this->getMedia(self::MEDIA)->count();
    }

    /**
     * Building a media URL asks the media for the model that owns it, because files are
     * filed under the account. Freshly loaded media has no such relation and would go
     * back to the database for a model we are already holding — which lazy loading
     * prevention turns into an exception. Hand it over instead.
     */
    public function bindMediaOwner(): static
    {
        if ($this->relationLoaded('media')) {
            $this->media->each(fn (Media $media) => $media->setRelation('model', $this));
        }

        return $this;
    }

    /**
     * A memory has to carry something of the moment — words or an attachment. Mood alone
     * leaves an empty milestone ticked off, which is what this rule exists to prevent.
     */
    public function hasSubstance(): bool
    {
        return filled($this->description) || $this->mediaCount() > 0;
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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
