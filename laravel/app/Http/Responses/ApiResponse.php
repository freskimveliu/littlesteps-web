<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    /**
     * `data` is always there, even when it is null — an endpoint with nothing to
     * say still has to say so. Stripping it meant a day with no prompt answered
     * `{"code":200}` and the app read undefined rather than "there isn't one".
     * A message, on the other hand, is genuinely optional.
     */
    public static function success(mixed $data, ?string $message = null, int $code = 200): JsonResponse
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            $data = $data->resolve();
        }

        return response()->json(array_filter([
            'data' => $data,
            'message' => $message,
            'code' => $code,
        ], fn ($value, $key) => $key === 'data' || $value !== null, ARRAY_FILTER_USE_BOTH), $code);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
