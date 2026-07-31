<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalogue;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\TemplatePrompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One age-appropriate prompt, picked at random.
 *
 * The app keeps whatever it gets for the rest of the day, so this being random
 * per call is fine — asking twice in a morning is the app's choice, not a bug.
 */
class ShowPromptController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $prompt = TemplatePrompt::query()
            ->forAge($child->ageInMonths())
            ->with('category')
            ->inRandomOrder()
            ->first();

        if (! $prompt) {
            return ApiResponse::success(null);
        }

        return ApiResponse::success([
            'id' => $prompt->id,
            'name' => $prompt->name,
            'icon' => $prompt->icon,
            'category' => new CategoryResource($prompt->category),
        ]);
    }
}
