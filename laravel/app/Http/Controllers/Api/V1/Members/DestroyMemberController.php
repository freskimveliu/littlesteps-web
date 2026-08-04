<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Members;

use App\Actions\Members\RemoveMember;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Authorized in the action: the creator may remove somebody, or somebody may
// remove themselves.
class DestroyMemberController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildMember $member, RemoveMember $remove): JsonResponse
    {
        $this->authorize('view', $child);
        abort_unless($member->child_id === $child->id, 404);

        $remove->handle($member, $child, $request->user());

        return ApiResponse::noContent();
    }
}
