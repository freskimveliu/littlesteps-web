<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Support\Admin\ChildSummary;
use App\Support\Progress\Metrics;
use Inertia\Inertia;
use Inertia\Response;

class ShowChildFamilyController extends Controller
{
    public function __invoke(int $child, Metrics $metrics): Response
    {
        $record = ChildSummary::find($child);

        $record->load('memberships.user:id,name,email');

        return Inertia::render('Admin/Children/Show/Family', [
            ...ChildSummary::for($record, $metrics->for($record)),
            'members' => $record->memberships->map(fn ($m) => [
                'id' => $m->id,
                'user' => $m->user?->only(['id', 'name', 'email']),
                'relation' => $m->relation,
                'role' => $m->role,
                'is_creator' => $m->user_id === $record->created_by_user_id,
            ]),
        ]);
    }
}
