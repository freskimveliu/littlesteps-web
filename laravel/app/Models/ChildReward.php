<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RewardStatus;
use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['child_id', 'child_achievement_id', 'type', 'status', 'content', 'claimed_at', 'generated_at'])]
class ChildReward extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const ARTWORK = 'artwork';

    protected function casts(): array
    {
        return [
            'type' => RewardType::class,
            'status' => RewardStatus::class,
            'claimed_at' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ARTWORK)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 400)
            ->format('webp')
            ->quality(85)
            ->optimize()
            ->nonQueued();
    }

    public function getAttribute($key)
    {
        if ($key === 'user_id') {
            return $this->child?->created_by_user_id;
        }

        return parent::getAttribute($key);
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<ChildAchievement, $this> */
    public function childAchievement(): BelongsTo
    {
        return $this->belongsTo(ChildAchievement::class);
    }
}
