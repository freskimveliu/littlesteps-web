<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Child;
use App\Models\User;

/**
 * The creator owns the child outright. Everyone else is a member, and a member
 * is either an editor — who may add memories but never change the child — or a
 * viewer, who may only look.
 */
class ChildPolicy
{
    public function view(User $user, Child $child): bool
    {
        return $this->membership($user, $child) !== null;
    }

    public function update(User $user, Child $child): bool
    {
        return $child->created_by_user_id === $user->id;
    }

    public function delete(User $user, Child $child): bool
    {
        return $child->created_by_user_id === $user->id;
    }

    /**
     * Who else gets into the map. The creator's alone: an editor may add memories
     * without being able to hand the key to anybody they like.
     */
    public function share(User $user, Child $child): bool
    {
        return $child->created_by_user_id === $user->id;
    }

    /** Adding and editing memories, milestones and chapters. */
    public function contribute(User $user, Child $child): bool
    {
        if ($child->created_by_user_id === $user->id) {
            return true;
        }

        return $this->membership($user, $child)?->role === MemberRole::Editor;
    }

    private function membership(User $user, Child $child): ?object
    {
        return $child->memberships->firstWhere('user_id', $user->id)
            ?? $child->memberships()->where('user_id', $user->id)->first();
    }
}
