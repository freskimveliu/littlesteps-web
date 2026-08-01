<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChildRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:60'],
            'birthday' => ['sometimes', 'date', 'before_or_equal:today'],
            'gender' => ['sometimes', Rule::enum(Gender::class)],
        ];
    }
}
