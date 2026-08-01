<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalogue;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * One age-appropriate prompt, picked at random and then held for the rest of
 * the day in the parent's own timezone — so asking again in the afternoon, or
 * from a second device, brings back the same question rather than a new one.
 */
class ShowPromptController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $zone = $request->user()->timezone ?: 'UTC';
        $key = "prompt:{$child->id}:".now($zone)->toDateString();

        $promptId = Cache::remember(
            $key,
            now($zone)->endOfDay(),
            fn () => Prompt::query()->forAge($child->ageInMonths())->inRandomOrder()->value('id'),
        );

        $prompt = $promptId ? Prompt::with('category')->find($promptId) : null;

        if (! $prompt) {
            Cache::forget($key);

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
