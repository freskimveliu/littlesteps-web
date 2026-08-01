<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function success(mixed $data, ?string $message = null, int $code = 200): JsonResponse
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            $data = $data->resolve();
        }

        return response()->json(array_filter([
            'data' => $data,
            'message' => $message,
            'code' => $code,
        ], fn ($value) => $value !== null), $code);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
