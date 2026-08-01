<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Devices;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestroyDeviceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['push_token' => ['required', 'string']]);

        $request->user()->devices()
            ->where('push_token', $request->string('push_token')->toString())
            ->delete();

        return ApiResponse::noContent();
    }
}
