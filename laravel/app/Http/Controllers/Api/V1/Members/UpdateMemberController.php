<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Members;

use App\Actions\Members\UpdateMember;
use App\Data\MemberData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateMemberRequest;
use App\Http\Resources\ChildMemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMember;
use Illuminate\Http\JsonResponse;

class UpdateMemberController extends Controller
{
    public function __invoke(
        UpdateMemberRequest $request,
        Child $child,
        ChildMember $member,
        UpdateMember $update,
    ): JsonResponse {
        $this->authorize('share', $child);
        abort_unless($member->child_id === $child->id, 404);

        $member = $update->handle($member, $child, MemberData::fromRequest($request));

        return ApiResponse::success(new ChildMemberResource($member->load('user')), 'Saved.');
    }
}
