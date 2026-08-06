<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use App\Support\Dimensions;
use App\Support\SmallerOriginal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
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
    public const ACCEPTS = ['image/jpeg', 'image/png', 'image/webp'];

    private ?bool $sealed = null;

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

    public function attachPhoto(UploadedFile $file): void
    {
        $photo = SmallerOriginal::of($file);
        $shape = Dimensions::of($photo);

        $this->addMedia($photo)
            ->withCustomProperties($shape)
            ->toMediaCollection(self::MEDIA);
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
            ->fit(Fit::Crop, 640, 640)
            ->format('webp')
            ->quality(80)
            ->optimize();
    }

    public function getAttribute($key)
    {
        if ($key === 'user_id') {
            return $this->created_by_user_id ?? $this->childOwnerId();
        }

        return parent::getAttribute($key);
    }

    /**
     * Whose folder this memory's files live in once its author is gone. A
     * memory outlives the account that wrote it — the child's family keeps it,
     * so the family keeps the photos too. PurgeUserAccount moves the files to
     * match before the author's row goes.
     *
     * Read straight off the table rather than through child(), which a memory
     * in this state is rarely holding and lazy loading would refuse to fetch.
     */
    private function childOwnerId(): ?int
    {
        $owner = Child::query()->whereKey($this->child_id)->value('created_by_user_id');

        return $owner === null ? null : (int) $owner;
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

    public function isSealed(): bool
    {
        if ($this->sealed !== null) {
            return $this->sealed;
        }

        if ($this->isFree()) {
            return false;
        }

        $milestone = $this->relationLoaded('milestone') ? $this->milestone : $this->milestone()->first();

        return (bool) $milestone?->isSealed();
    }

    public function bindSeal(bool $sealed): static
    {
        $this->sealed = $sealed;

        return $this;
    }

    /**
     * What may be done to this memory, in the same shape a chapter and a milestone
     * answer in. `$mayWrite` arrives from the resource for the reason given on
     * ChildChapter::abilities().
     *
     * @return array<string, bool>
     */
    public function abilities(bool $mayWrite): array
    {
        return [
            'edit' => $mayWrite && ! $this->isSealed(),
            'delete' => $mayWrite && $this->isDeletable(),
        ];
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

    /** Null until somebody edits it, and not always whoever wrote it. @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
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
