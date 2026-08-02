<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Support\Admin\ChildSummary;
use App\Support\Progress\Metrics;
use Inertia\Inertia;
use Inertia\Response;

class ShowChildGiftsController extends Controller
{
    public function __invoke(int $child, Metrics $metrics): Response
    {
        $record = ChildSummary::find($child);

        $record->load('rewards.childTrophy');

        return Inertia::render('Admin/Children/Show/Gifts', [
            ...ChildSummary::for($record, $metrics->for($record)),
            'rewards' => $record->rewards->map(fn ($reward) => [
                'id' => $reward->id,
                'type' => $reward->type,
                'status' => $reward->status,
                'trophy' => $reward->childTrophy?->name,
                'claimed_at' => $reward->claimed_at?->toIso8601String(),
                'generated_at' => $reward->generated_at?->toIso8601String(),
                'has_content' => $reward->content !== null,
            ]),
        ]);
    }
}
