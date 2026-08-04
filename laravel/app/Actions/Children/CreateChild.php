<?php

declare(strict_types=1);

namespace App\Actions\Children;

use App\Data\ChildData;
use App\Enums\MemberRole;
use App\Models\Child;
use App\Models\User;
use App\Support\SmallerOriginal;
use Illuminate\Support\Facades\DB;

class CreateChild
{
    public function __construct(private readonly ProvisionChild $provision) {}

    public function handle(User $user, ChildData $data): Child
    {
        $child = DB::transaction(function () use ($user, $data) {
            $child = Child::create([
                ...$data->toAttributes(),
                'created_by_user_id' => $user->id,
            ]);

            $child->memberships()->create([
                'user_id' => $user->id,
                'relation' => $data->relation,
                'role' => MemberRole::Editor,
            ]);

            $this->provision->handle($child);

            // xp is not fillable and defaults in the database, so the model in
            // memory carries null until the row is read back.
            return $child->refresh();
        });

        // Outside the transaction: resizing a phone photo takes longer than every
        // insert above put together, and a rollback could not take the file back.
        if ($data->photo) {
            $child->addMedia(SmallerOriginal::of($data->photo))->toMediaCollection(Child::PHOTO);
        }

        return $child;
    }
}
