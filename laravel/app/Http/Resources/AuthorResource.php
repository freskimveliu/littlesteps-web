<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Whoever wrote or last changed something. A name and nothing else — an account's
 * email is its own business.
 *
 * @mixin User
 */
class AuthorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isYou' => $this->id === $request->user()?->id,
        ];
    }
}
