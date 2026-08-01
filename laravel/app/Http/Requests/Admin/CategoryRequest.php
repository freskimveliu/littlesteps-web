<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Icon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {

        return [
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', Rule::enum(Icon::class)],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
