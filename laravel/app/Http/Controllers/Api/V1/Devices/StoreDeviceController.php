<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Devices;

use App\Enums\DevicePlatform;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreDeviceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'push_token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::enum(DevicePlatform::class)],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        // Unique on the token, so a re-register moves the device to this user
        // rather than leaving a stale row pointing at the old one.
        $device = Device::updateOrCreate(
            ['push_token' => $validated['push_token']],
            [...$validated, 'user_id' => $request->user()->id],
        );

        return ApiResponse::success([
            'id' => $device->id,
            'platform' => $device->platform,
        ], 'Device registered.', 201);
    }
}
