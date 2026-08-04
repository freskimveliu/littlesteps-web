<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Members;

use App\Actions\Members\AddMember;
use App\Data\MemberData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMemberRequest;
use App\Http\Resources\ChildMemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class StoreMemberController extends Controller
{
    public function __invoke(StoreMemberRequest $request, Child $child, AddMember $add): JsonResponse
    {
        $this->authorize('share', $child);

        $member = $add->handle($child, MemberData::fromRequest($request));

        return ApiResponse::success(
            new ChildMemberResource($member->load('user')),
            "{$member->user->name} can see the adventure now.",
            201,
        );
    }
}
