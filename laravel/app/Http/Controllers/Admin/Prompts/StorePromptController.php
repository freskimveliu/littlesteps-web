<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prompts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromptRequest;
use App\Models\Prompt;
use Illuminate\Http\RedirectResponse;

class StorePromptController extends Controller
{
    public function __invoke(PromptRequest $request): RedirectResponse
    {
        Prompt::create($request->validated());

        return back()->with('success', 'Prompt created.');
    }
}
