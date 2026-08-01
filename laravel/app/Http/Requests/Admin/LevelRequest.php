<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Icon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('level')?->id;

        return [
            'name' => ['required', 'string', 'max:60'],
            'icon' => ['required', Rule::enum(Icon::class)],
            // The ladder is ordered by min_xp, so two levels cannot share a rung.
            'min_xp' => ['required', 'integer', 'min:0', Rule::unique('levels', 'min_xp')->ignore($id)],
            'is_active' => ['boolean'],
        ];
    }
}
