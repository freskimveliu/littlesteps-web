<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Icon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('chapter')?->id;

        return [
            'slug' => ['required', 'string', 'max:60', Rule::unique('template_milestones', 'slug')->ignore($id)],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', Rule::enum(Icon::class)],
            'months_from' => ['nullable', 'integer', 'min:0', 'max:216'],
            'xp' => ['required', 'integer', 'min:0', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_editable' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
