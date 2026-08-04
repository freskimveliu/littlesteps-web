<?php

declare(strict_types=1);

namespace App\Data\Concerns;

/**
 * A patch is partial, and "clear the description" and "leave it alone" arrive as
 * the same null otherwise — so a Data object keeps the keys that actually came in.
 *
 * The class using this promotes `private array $sent` in its own constructor;
 * a readonly class cannot take a defaulted property from a trait.
 */
trait RemembersWhatWasSent
{
    public function sent(string $key): bool
    {
        return in_array($key, $this->sent, true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function only(array $attributes): array
    {
        return array_intersect_key($attributes, array_flip($this->sent));
    }
}
