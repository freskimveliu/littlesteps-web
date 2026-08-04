<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Devices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

class StoreDeviceController extends Controller
{
    public function __invoke(StoreDeviceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Unique on the token, so a re-register moves the device to this user
        // rather than leaving a stale row pointing at the old one.
        $device = Device::updateOrCreate(
            ['push_token' => $validated['push_token']],
            [...$validated, 'user_id' => $request->user()->id],
        );

        return ApiResponse::success(new DeviceResource($device), 'Device registered.', 201);
    }
}
