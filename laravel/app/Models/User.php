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
use Illuminate\Database\UniqueConstraintViolationException;
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

    /**
     * What a share code is spelled from. No O or 0, no I or 1, no S or 5, no B or
     * 8, no Z or 2 — this gets read down a phone line to a grandparent and typed
     * in by hand, and a pair that looks alike is a code that does not work.
     */
    private const SHARE_ALPHABET = '34679ACDEFGHJKLMNPQRTUVWXY';

    private const SHARE_LENGTH = 6;

    /** How many times a losing race for a share code is worth re-running. */
    private const SHARE_ATTEMPTS = 5;

    /**
     * Every account is findable by another the moment it exists — a device that
     * has never seen a sign-up screen included, since those have no email to be
     * found by. Assigned here rather than in a controller so no way of making a
     * user can skip it.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->share_code ??= self::freshShareCode();
        });
    }

    /**
     * Save a new account, drawing another share code if that one was taken between
     * choosing it and writing it — freshShareCode() cannot see an uncommitted
     * insert, so the unique index gets the last word and this listens for it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function open(array $attributes): self
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return static::create($attributes);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::SHARE_ATTEMPTS || ! str_contains($e->getMessage(), 'share_code')) {
                    throw $e;
                }
            }
        }
    }

    /** Soft-deleted accounts still hold their code — the unique index says so. */
    private static function freshShareCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < self::SHARE_LENGTH; $i++) {
                $code .= self::SHARE_ALPHABET[random_int(0, strlen(self::SHARE_ALPHABET) - 1)];
            }
        } while (self::withTrashed()->where('share_code', $code)->exists());

        return $code;
    }

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
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
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
     * What has to be typed back to delete the account. The name it goes by,
     * unless it never gave one — a device that has not signed up still has a
     * share code, and something has to be typed.
     */
    public function deletionPhrase(): string
    {
        return filled($this->name) ? $this->name : $this->share_code;
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
