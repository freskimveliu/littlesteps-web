<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Keeps every file under the account that owns it:
 *
 *   users/{userId}/{collection}/{mediaId}/file.jpg
 *   users/{userId}/{collection}/{mediaId}/conversions/thumb.webp
 *
 * Deleting an account is then a single directory removal, and nothing of one
 * family's ever sits in another's folder.
 */
class UserScopedPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->basePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media).'/responsive/';
    }

    private function basePath(Media $media): string
    {
        $model = $media->model;

        $userId = match (true) {
            $model instanceof User => $model->getKey(),
            default => $model->user_id ?? 'orphaned',
        };

        return "users/{$userId}/{$media->collection_name}/{$media->getKey()}";
    }
}
