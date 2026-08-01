<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prompts;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\RedirectResponse;

class DestroyPromptController extends Controller
{
    public function __invoke(Prompt $prompt): RedirectResponse
    {
        $prompt->delete();

        return back()->with('success', 'Prompt removed.');
    }
}
