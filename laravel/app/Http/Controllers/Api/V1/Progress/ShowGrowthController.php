<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Support\Progress\GrowthSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowGrowthController extends Controller
{
    public function __invoke(Request $request, Child $child, GrowthSeries $growth): JsonResponse
    {
        $this->authorize('view', $child);

        return ApiResponse::success($growth->for($child));
    }
}
