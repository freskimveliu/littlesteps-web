<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MemberRole;
use App\Enums\Relation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'user_id', 'relation', 'role'])]
class ChildMember extends Model
{
    protected function casts(): array
    {
        return [
            'relation' => Relation::class,
            'role' => MemberRole::class,
        ];
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
