<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['created_by_user_id', 'name', 'birthday', 'gender'])]
class Child extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const PHOTO = 'photo';

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'gender' => Gender::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTO)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/heic']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 240, 240)
            ->format('webp')
            ->quality(80)
            ->optimize()
            ->nonQueued();
    }

    /**
     * Media is filed under the account that owns the child, so the path
     * generator can reach a user id from here.
     */
    public function getAttribute($key)
    {
        if ($key === 'user_id') {
            return $this->created_by_user_id;
        }

        return parent::getAttribute($key);
    }

    public function ageInMonths(?\DateTimeInterface $on = null): int
    {
        return max(0, (int) floor($this->birthday->diffInMonths($on ?? now())));
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<ChildMember, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(ChildMember::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'child_members')
            ->withPivot(['relation', 'role'])
            ->withTimestamps();
    }

    /** @return HasMany<ChildChapter, $this> */
    public function chapters(): HasMany
    {
        return $this->hasMany(ChildChapter::class);
    }

    /**
     * Held for the request: every chapter in the map asks whether it is the last
     * one, and eager loading hands them all the same child instance.
     */
    public function chapterCount(): int
    {
        return once(fn () => $this->chapters()->count());
    }

    /** @return HasMany<ChildMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(ChildMilestone::class);
    }

    /** @return HasMany<ChildEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(ChildEntry::class);
    }

    /** @return HasMany<ChildTrophy, $this> */
    public function trophies(): HasMany
    {
        return $this->hasMany(ChildTrophy::class);
    }

    /** @return HasMany<ChildReward, $this> */
    public function rewards(): HasMany
    {
        return $this->hasMany(ChildReward::class);
    }

    public function photoUrl(): ?string
    {
        $photo = $this->getFirstMedia(self::PHOTO);

        return $photo ? MediaUrl::for($photo) : null;
    }

    public function photoThumbUrl(): ?string
    {
        $photo = $this->getFirstMedia(self::PHOTO);

        return $photo ? MediaUrl::for($photo, 'thumb') : null;
    }
}
