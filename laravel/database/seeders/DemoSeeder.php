<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Chapters\CompleteChapter;
use App\Actions\Children\CreateChild;
use App\Actions\Progress\EvaluateTrophies;
use App\Data\ChildData;
use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\Mood;
use App\Enums\Relation;
use App\Enums\RewardStatus;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\ChildMilestone;
use App\Models\ChildReward;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Make-believe families, so the admin has something to look at locally.
 *
 * Memories are written straight to the table rather than through RecordEntry:
 * the daily cap would reject a back-dated run of them. XP, chapters and
 * trophies then go through the real actions, so the numbers add up the way
 * the app would have added them up itself.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'timezone' => 'Europe/Tirane',
        ]);

        $admin->forceFill(['is_admin' => true])->save();

        $arta = User::factory()->create(['name' => 'Arta Krasniqi', 'timezone' => 'Europe/Tirane']);
        $liridon = User::factory()->create(['name' => 'Liridon Berisha', 'timezone' => 'Europe/Tirane']);

        // A year and a half in, two chapters closed — the account support hears from.
        $this->family($admin, 'Liza', 18, Gender::Girl, Relation::Mother, everyDays: 12, chapters: 2);

        // Elena has been going the longest: five chapters, and the gifts to show for it.
        $this->family($arta, 'Elena', 38, Gender::Girl, Relation::Mother, everyDays: 9, chapters: 5);

        // Barely started, so the empty-ish end of the range is represented too.
        $noel = $this->family($arta, 'Noel', 5, Gender::Boy, Relation::Mother, everyDays: 4, chapters: 0, memories: 9);
        $this->family($liridon, 'Rron', 27, Gender::Boy, Relation::Father, everyDays: 30, chapters: 0, memories: 3);

        // A grandparent who was invited in, so the family tab has something to show.
        $noel->memberships()->create([
            'user_id' => $liridon->id,
            'relation' => Relation::Grandparent,
            'role' => MemberRole::Viewer,
        ]);

        // Someone who opened the app and never signed up — the guest row the API creates.
        User::factory()->create(['name' => 'Parent', 'email' => null, 'password' => null]);

        $this->gifts();
    }

    private function family(
        User $user,
        string $name,
        int $ageMonths,
        Gender $gender,
        Relation $relation,
        int $everyDays,
        int $chapters,
        ?int $memories = null,
    ): Child {
        $child = app(CreateChild::class)->handle($user, new ChildData(
            name: $name,
            birthday: now()->subMonths($ageMonths)->toDateString(),
            gender: $gender,
            relation: $relation,
        ));

        $open = $child->milestones()
            ->visible()
            ->whereHas('chapter', fn ($q) => $q->orderBy('sort_order'))
            ->with('chapter')
            ->get()
            ->sortBy(fn (ChildMilestone $m) => [$m->chapter->sort_order, $m->sort_order])
            ->values();

        // Chapters only close when every milestone in them is filled, so the
        // ones being closed are recorded whole and the rest trail off.
        $closing = $child->chapters()->orderBy('sort_order')->take($chapters)->pluck('id');

        $fill = $open->filter(fn (ChildMilestone $m) => $closing->contains($m->child_chapter_id));
        $rest = $open->reject(fn (ChildMilestone $m) => $closing->contains($m->child_chapter_id));

        $recording = $fill->concat($rest->take($memories ?? 8))->values();

        $xp = 0;

        foreach ($recording as $i => $milestone) {
            $this->write($child, $user, $i * $everyDays, $milestone->id, $milestone->name);
            $xp += $milestone->xp;
        }

        $child->increment('xp', $xp);

        foreach ($closing as $id) {
            app(CompleteChapter::class)->handle(ChildChapter::findOrFail($id), $user);
        }

        app(EvaluateTrophies::class)->handle($child->refresh());

        $this->streak($user, $recording->count());

        return $child;
    }

    private function write(Child $child, User $user, int $daysAgo, int $milestoneId, string $about): void
    {
        $child->entries()->create([
            'child_milestone_id' => $milestoneId,
            'description' => "The day {$child->name} finally did it — {$about}.",
            'date' => now()->subDays($daysAgo)->toDateString(),
            'mood' => fake()->randomElement(Mood::cases()),
            'created_by_user_id' => $user->id,
        ]);
    }

    private function streak(User $user, int $written): void
    {
        $days = min($written, 9);

        $user->forceFill([
            'current_streak' => $days,
            'longest_streak' => max($days, $user->longest_streak),
            'last_entry_date' => now()->toDateString(),
        ])->save();
    }

    /** Moves a few reserved gifts along, so every status is represented. */
    private function gifts(): void
    {
        $rewards = ChildReward::query()->orderBy('id')->get();

        $rewards->get(0)?->forceFill([
            'status' => RewardStatus::Ready,
            'claimed_at' => now()->subDays(3),
            'generated_at' => now()->subDays(3),
            'content' => 'Once upon a time, on a Tuesday that nobody wrote down…',
        ])->save();

        $rewards->get(1)?->forceFill([
            'status' => RewardStatus::Generating,
            'claimed_at' => now()->subMinutes(20),
        ])->save();

        $rewards->get(2)?->forceFill([
            'status' => RewardStatus::Failed,
            'claimed_at' => now()->subDay(),
        ])->save();
    }
}
