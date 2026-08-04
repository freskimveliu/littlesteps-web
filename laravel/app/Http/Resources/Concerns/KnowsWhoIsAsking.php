<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use App\Models\Child;
use Illuminate\Http\Request;

/**
 * Whether the caller may write to the child this payload is about.
 *
 * Taken from the route, not the row: it is the child the controller already
 * authorized, so the answer is worked out once for the whole payload instead of
 * once per chapter and milestone. Every route answering with one of these
 * resources lives under `children/{child}`.
 */
trait KnowsWhoIsAsking
{
    protected function mayWrite(Request $request): bool
    {
        $child = $request->route('child');

        return $child instanceof Child && $child->allowsWriting($request->user());
    }
}
