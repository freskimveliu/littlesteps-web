<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Devices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyDeviceRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class DestroyDeviceController extends Controller
{
    public function __invoke(DestroyDeviceRequest $request): JsonResponse
    {
        $request->user()->devices()
            ->where('push_token', $request->string('push_token')->toString())
            ->delete();

        return ApiResponse::noContent();
    }
}
