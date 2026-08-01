<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Icon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChapterRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:160'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'months_from' => ['nullable', 'integer', 'min:0', 'max:216'],
        ];
    }
}
