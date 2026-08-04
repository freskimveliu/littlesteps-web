<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Icon;
use App\Support\Limits;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'child_id', 'chapter_id', 'name', 'description', 'icon',
    'months_from', 'xp', 'sort_order', 'is_editable',
    'created_by_user_id', 'updated_by_user_id',
])]
class ChildChapter extends Model
{
    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'is_editable' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return HasMany<ChildMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(ChildMilestone::class);
    }

    /**
     * Everything a chapter needs to draw itself. Every endpoint answering with one
     * wants exactly this, and Limits counts recorded milestones off `entry` rather
     * than going back to the database per chapter.
     *
     * @return array<int|string, mixed>
     */
    public static function map(): array
    {
        return [
            'child',
            'milestones' => fn ($query) => $query->orderBy('sort_order')
                ->with(['category', 'properties', 'entry.properties', 'entry.media', 'entry.creator', 'entry.editor', 'child']),
        ];
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * A row copied from the catalogue rather than written by a parent. A guided
     * chapter is the age spine of the map — "Month 5" sits where it does because
     * that is when it happens — so its place and its age are not the parent's to
     * change, even though its name and its fate are.
     */
    public function isGuided(): bool
    {
        return ! $this->is_editable;
    }

    /**
     * A chapter can go while it is still unfinished — a guided one included, since
     * a parent who found the app at a year old has months of map behind them that
     * no photo will ever fill. Once it is finished its gift has been given, and
     * the last chapter always stays: an empty map is not a journey.
     */
    public function isDeletable(): bool
    {
        return ! $this->isCompleted() && $this->child->chapterCount() > 1;
    }

    /**
     * Everything a parent may do to this chapter, decided here so the app never
     * has to know the rules — it renders the buttons this says it can.
     *
     * `reorder` is permission, not position: whether a neighbour exists to swap
     * with is a question about the list the app is already holding.
     *
     * A guided chapter can still be renamed and deleted — the map is the parent's —
     * but not reordered: it is the age spine the rest of the map hangs off, and the
     * parent's own chapters move around it. That is the one thing is_editable
     * decides; everything else it only records.
     *
     * Finishing is the parent's own act, and it seals what it finished: a recap
     * card already shared should not go out of date behind their back. Only
     * `reorder` survives, because where a finished chapter sits among its
     * siblings is a fact about the journey, not about the keepsake.
     *
     * A chapter the child has not grown into is a preview, not a shelf. It can be
     * renamed and deleted — a parent who knows they will never do this one should
     * be able to clear it — but nothing may be added to it or recorded in it, and
     * it does not move: a preview is a place on the calendar, and dragging it does
     * not change when the child gets there.
     *
     * Who is asking comes first: a grandparent who may only look is handed every
     * one of these false, so the app never draws a button that answers 403.
     * `viewRecap` is the exception, because reading is not writing. It arrives as
     * `$mayWrite` because it is the same for every row of a payload.
     *
     * @return array<string, bool>
     */
    public function abilities(bool $mayWrite): array
    {
        $limits = app(Limits::class);
        $completed = $this->isCompleted();
        $child = $this->child;
        $locked = $child ? ! $this->isUnlockedFor($child) : false;

        return [
            'rename' => $mayWrite && ! $completed,
            'reorder' => $mayWrite && ! $this->isPinned($child),
            'delete' => $mayWrite && $this->isDeletable(),
            'complete' => $mayWrite && $limits->canCompleteChapter($this),
            'addMilestone' => $mayWrite && ! $completed && ! $locked && $limits->canAddCustomMilestone($this),
            'viewRecap' => $completed,
        ];
    }

    public function isUnlockedFor(Child $child): bool
    {
        return $this->months_from === null || $child->ageInMonths() >= $this->months_from;
    }

    /**
     * Where this chapter sits is not the parent's to change.
     *
     * Two reasons, and they land on the same answer: a guided chapter is the age
     * spine the rest of the map hangs off, and one the child has not grown into is
     * a date rather than a place in a list. Dragging either does not change when
     * the child gets there.
     */
    public function isPinned(?Child $child = null): bool
    {
        return $this->isGuided() || ($child !== null && ! $this->isUnlockedFor($child));
    }
}
