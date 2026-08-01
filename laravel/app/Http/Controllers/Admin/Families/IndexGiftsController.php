<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Models\ChildReward;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every gift a child has earned, and where it got to. This is the operational
 * page: a generation that failed leaves a row here and nowhere else.
 */
class IndexGiftsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = ChildReward::query()->with(['child:id,name', 'childTrophy:id,name']);

        if ($status = RewardStatus::tryFrom((string) $request->query('status'))) {
            $query->where('status', $status);
        }

        $gifts = $query->orderByDesc('id')->paginate(40)->withQueryString();

        $gifts->getCollection()->transform(fn (ChildReward $gift) => [
            'id' => $gift->id,
            'type' => $gift->type,
            'status' => $gift->status,
            'child' => $gift->child?->only(['id', 'name']),
            'trophy' => $gift->childTrophy?->name,
            'claimed_at' => $gift->claimed_at?->toIso8601String(),
            'generated_at' => $gift->generated_at?->toIso8601String(),
            'is_stuck' => $gift->status === RewardStatus::Generating
                && $gift->claimed_at?->lt(now()->subHour()),
        ]);

        return Inertia::render('Admin/Children/Gifts', [
            'gifts' => $gifts,
            'filters' => ['status' => $request->query('status')],
            'counts' => collect(RewardStatus::cases())
                ->mapWithKeys(fn (RewardStatus $s) => [$s->value => ChildReward::where('status', $s)->count()]),
        ]);
    }
}
