<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Concerns;

use App\Models\Child;

/**
 * A memory belongs to a life, so it cannot be dated before one started. Without
 * this the growth chart plots the reading at a negative age.
 */
trait BoundsTheDate
{
    /** @return array<int, string> */
    protected function notBeforeBirth(): array
    {
        $child = $this->route('child');

        return $child instanceof Child
            ? ['after_or_equal:'.$child->birthday->toDateString()]
            : [];
    }
}
