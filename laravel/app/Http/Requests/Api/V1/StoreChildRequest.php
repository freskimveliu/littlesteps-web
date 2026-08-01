<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\Gender;
use App\Enums\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChildRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'birthday' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'relation' => ['required', Rule::enum(Relation::class)],
            'photo' => ['sometimes', 'image', 'max:20480'],
        ];
    }
}
