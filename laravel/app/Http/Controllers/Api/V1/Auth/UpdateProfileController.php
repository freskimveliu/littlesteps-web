<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'language' => ['sometimes', 'string', 'in:en,sq'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'settings' => ['sometimes', 'array'],
            'settings.*' => ['boolean'],
        ]);

        $user->fill(collect($validated)->except('settings')->all())->save();

        foreach ($validated['settings'] ?? [] as $key => $value) {
            if ($setting = SettingKey::tryFrom((string) $key)) {
                $user->putSetting($setting, (bool) $value);
            }
        }

        return ApiResponse::success(new UserResource($user->fresh()->load('settings')), 'Profile updated.');
    }
}
