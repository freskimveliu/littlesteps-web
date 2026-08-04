<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Prompt */
class PromptResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
