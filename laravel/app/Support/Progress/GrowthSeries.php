<?php

declare(strict_types=1);

namespace App\Support\Progress;

use App\Enums\PropertyKey;
use App\Models\Child;
use App\Models\ChildEntryProperty;
use Illuminate\Support\Collection;

/**
 * The growth chart, computed on the server: every chartable measurement with the
 * child's age in months on the day it was recorded.
 */
class GrowthSeries
{
    /** @return Collection<int, array<string, mixed>> */
    public function for(Child $child): Collection
    {
        return $this->readings($child)
            ->groupBy('key')
            ->map(fn (Collection $rows, string $key) => [
                'key' => $key,
                'unit' => PropertyKey::from($key)->unit(),
                'points' => $rows->map(fn ($row) => [
                    'date' => $this->day($row->date),
                    // Clamped like Child::ageInMonths(): a row written before entries
                    // were bounded to the birthday must not draw the chart backwards.
                    'ageMonths' => max(0, (int) floor($child->birthday->diffInMonths($row->date))),
                    'value' => (float) $row->value,
                ])->values(),
            ])
            ->values();
    }

    /** @return Collection<int, ChildEntryProperty> */
    private function readings(Child $child): Collection
    {
        return ChildEntryProperty::query()
            ->chartable()
            ->whereNotNull('value')
            ->join('child_entries', 'child_entries.id', '=', 'child_entry_properties.child_entry_id')
            ->where('child_entries.child_id', $child->id)
            ->orderBy('child_entries.date')
            ->get(['child_entry_properties.key', 'child_entry_properties.value', 'child_entries.date']);
    }

    /** The joined column arrives uncast, so it may be a string or a date. */
    private function day(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
    }
}
