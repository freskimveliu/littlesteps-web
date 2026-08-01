<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AchievementMetric;
use App\Enums\Icon;
use App\Enums\RewardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BadgeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {

        return [
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', Rule::enum(Icon::class)],
            'metric' => ['required', Rule::enum(AchievementMetric::class)],
            'threshold' => ['required', 'integer', 'min:1'],
            'xp' => ['required', 'integer', 'min:0', 'max:10000'],
            'reward' => ['nullable', Rule::enum(RewardType::class)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
