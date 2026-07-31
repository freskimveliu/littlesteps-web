<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Progress;

use App\Enums\PropertyKey;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntryProperty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The growth chart, computed here rather than in the app: every chartable
 * measurement with the child's age in months at the time it was recorded.
 */
class ShowGrowthController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $readings = ChildEntryProperty::query()
            ->chartable()
            ->whereNotNull('value')
            ->join('child_entries', 'child_entries.id', '=', 'child_entry_properties.child_entry_id')
            ->where('child_entries.child_id', $child->id)
            ->orderBy('child_entries.date')
            ->get(['child_entry_properties.key', 'child_entry_properties.value', 'child_entries.date']);

        $series = $readings->groupBy('key')->map(fn ($rows, $key) => [
            'key' => $key,
            'unit' => PropertyKey::from($key)->unit(),
            'points' => $rows->map(fn ($row) => [
                'date' => $row->date instanceof \DateTimeInterface ? $row->date->format('Y-m-d') : (string) $row->date,
                'ageMonths' => (int) floor($child->birthday->diffInMonths($row->date)),
                'value' => (float) $row->value,
            ])->values(),
        ])->values();

        return ApiResponse::success($series);
    }
}
