<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A running order the parent submitted, held against the one that exists.
 *
 * The whole list arrives in a single call rather than a nudge per row, because
 * two parents each moving something a place would otherwise land on the same
 * sort_order and the map would shuffle itself.
 *
 * Some rows are not theirs to move — a guided chapter, a milestone that names a
 * date, anything the child has not grown into. Those are not frozen in place,
 * which would stop the map ever changing; they keep their order *relative to
 * each other*, so the parent's own rows still slide anywhere around them.
 */
final readonly class Reordering
{
    /**
     * @param  Collection<int, int>  $ids  as submitted
     * @param  Collection<int, Model>  $current  in the order they sit now
     */
    private function __construct(
        private Collection $ids,
        private Collection $current,
    ) {}

    /**
     * @param  array<int, mixed>  $submitted
     * @param  Collection<int, Model>  $current
     */
    public static function of(array $submitted, Collection $current): self
    {
        return new self(collect($submitted)->map(fn ($id) => (int) $id), $current);
    }

    /** The same rows, each named once — a reordering may not add, drop or repeat one. */
    public function matches(): bool
    {
        $own = $this->current->pluck('id');

        return $this->ids->count() === $own->count()
            && $this->ids->duplicates()->isEmpty()
            && $this->ids->diff($own)->isEmpty();
    }

    /** @param callable(Model): bool $isPinned */
    public function keepsPinned(callable $isPinned): bool
    {
        $pinned = $this->current->filter($isPinned)->pluck('id')->values()->all();

        return $this->ids->filter(fn (int $id) => in_array($id, $pinned, true))->values()->all() === $pinned;
    }

    /**
     * Renumbered in tens, so a later insert has somewhere to land without
     * rewriting the rows either side of it.
     *
     * `$rows` is a closure rather than the relation itself because constraints
     * accumulate: reusing one instance would build up `id = 1 and id = 2` down
     * the loop and stop matching anything after the first row.
     *
     * @param  callable(): HasMany<Model, Model>  $rows
     */
    public function applyTo(callable $rows, int $userId): void
    {
        DB::transaction(function () use ($rows, $userId) {
            foreach ($this->ids as $i => $id) {
                $rows()->whereKey($id)->update([
                    'sort_order' => ($i + 1) * 10,
                    'updated_by_user_id' => $userId,
                ]);
            }
        });
    }
}
