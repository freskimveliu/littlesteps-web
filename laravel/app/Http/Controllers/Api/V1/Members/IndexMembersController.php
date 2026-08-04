<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Members;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildMemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexMembersController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $members = $child->memberships()->with('user')->orderBy('id')->get();

        return ApiResponse::success(ChildMemberResource::collection($members));
    }
}
