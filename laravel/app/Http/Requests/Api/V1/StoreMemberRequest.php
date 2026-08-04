<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\MemberRole;
use App\Enums\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'share_code' => ['required', 'string', 'size:6'],
            'relation' => ['required', Rule::enum(Relation::class)],
            'role' => ['required', Rule::enum(MemberRole::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'share_code.size' => 'A sharing code is six characters long.',
        ];
    }
}
