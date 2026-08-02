<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChildRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'birthday' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
        ];
    }
}
