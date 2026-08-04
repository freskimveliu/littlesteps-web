<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Children;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyChildRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class DestroyChildController extends Controller
{
    public function __invoke(DestroyChildRequest $request, Child $child): JsonResponse
    {
        $this->authorize('delete', $child);

        $child->delete();

        return ApiResponse::noContent();
    }
}
