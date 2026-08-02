<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Language;
use App\Models\Concerns\HasSettings;
use App\Support\MediaUrl;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'email', 'password', 'language', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasSettings, InteractsWithMedia, Notifiable, SoftDeletes;

    public const PHOTO = 'photo';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'language' => Language::class,
            'is_admin' => 'boolean',
            'last_entry_date' => 'date',
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
            ->fit(Fit::Crop, 160, 160)
            ->format('webp')
            ->quality(80)
            ->optimize()
            ->nonQueued();
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

    public function isRegistered(): bool
    {
        return $this->email !== null && $this->password !== null;
    }

    /**
     * Children this user created — the only ones they may edit or delete.
     *
     * @return HasMany<Child, $this>
     */
    public function ownedChildren(): HasMany
    {
        return $this->hasMany(Child::class, 'created_by_user_id');
    }

    /**
     * Every child they can reach: their own, plus any shared with them.
     *
     * @return BelongsToMany<Child, $this>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_members')
            ->withPivot(['relation', 'role'])
            ->withTimestamps();
    }

    /** @return HasMany<Device, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
