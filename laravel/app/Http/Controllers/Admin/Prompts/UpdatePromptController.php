<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prompts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromptRequest;
use App\Models\TemplatePrompt;
use Illuminate\Http\RedirectResponse;

class UpdatePromptController extends Controller
{
    public function __invoke(PromptRequest $request, TemplatePrompt $prompt): RedirectResponse
    {
        $prompt->update($request->validated());

        return back()->with('success', 'Prompt saved.');
    }
}
