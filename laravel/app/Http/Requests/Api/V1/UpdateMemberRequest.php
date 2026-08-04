<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\MemberRole;
use App\Enums\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'relation' => ['sometimes', Rule::enum(Relation::class)],
            'role' => ['sometimes', Rule::enum(MemberRole::class)],
        ];
    }
}
