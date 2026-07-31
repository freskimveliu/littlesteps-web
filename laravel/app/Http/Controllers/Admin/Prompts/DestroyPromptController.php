<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prompts;

use App\Http\Controllers\Controller;
use App\Models\TemplatePrompt;
use Illuminate\Http\RedirectResponse;

class DestroyPromptController extends Controller
{
    public function __invoke(TemplatePrompt $prompt): RedirectResponse
    {
        $prompt->delete();

        return back()->with('success', 'Prompt removed.');
    }
}
