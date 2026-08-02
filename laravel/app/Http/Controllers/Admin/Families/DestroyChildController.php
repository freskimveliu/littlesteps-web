<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\RedirectResponse;

class DestroyChildController extends Controller
{
    /**
     * The journey goes with the child: chapters, milestones, memories, trophies
     * and gifts all hang off child_id and cascade in the database.
     */
    public function __invoke(Child $child): RedirectResponse
    {
        $name = $child->name;

        $child->delete();

        return back()->with('success', "{$name} and everything recorded for them are gone.");
    }
}
