<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Support\SmallerOriginal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreUserPhotoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['photo' => ['required', 'image', 'max:20480']]);

        $user = $request->user();
        $user->addMedia(SmallerOriginal::of($request->file('photo')))->toMediaCollection(User::PHOTO);

        return ApiResponse::success(new UserResource($user->fresh()->load('settings')), 'Photo updated.');
    }
}
