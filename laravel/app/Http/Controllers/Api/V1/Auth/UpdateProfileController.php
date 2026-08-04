<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Users\UpdateProfile;
use App\Data\ProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfile $update): JsonResponse
    {
        $user = $update->handle($request->user(), ProfileData::fromRequest($request));

        return ApiResponse::success(new UserResource($user), 'Profile updated.');
    }
}
