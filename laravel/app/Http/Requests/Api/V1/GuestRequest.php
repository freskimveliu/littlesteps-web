<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class GuestRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'in:en'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ];
    }
}
