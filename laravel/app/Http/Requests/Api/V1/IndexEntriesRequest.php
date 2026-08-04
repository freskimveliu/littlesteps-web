<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Capped: unbounded, one call could ask for the whole timeline and mint a signed
 * link for every attachment on it.
 */
class IndexEntriesRequest extends FormRequest
{
    private const PER_PAGE = 30;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page') ?: self::PER_PAGE;
    }
}
