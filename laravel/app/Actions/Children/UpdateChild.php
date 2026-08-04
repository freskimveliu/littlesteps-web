<?php

declare(strict_types=1);

namespace App\Actions\Children;

use App\Data\ChildChangeData;
use App\Models\Child;
use App\Support\SmallerOriginal;

class UpdateChild
{
    public function handle(Child $child, ChildChangeData $data): Child
    {
        $child->update($data->toAttributes());

        // Never inside a transaction — see CreateChild.
        if ($data->photo) {
            $child->addMedia(SmallerOriginal::of($data->photo))->toMediaCollection(Child::PHOTO);
        }

        return $child->fresh();
    }
}
