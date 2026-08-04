<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalogue;

use App\Enums\AppSettingKey;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppSetting;
use App\Models\Category;
use App\Support\Progress\LevelLadder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * The bits of the catalogue the app renders but does not own: categories, the
 * level ladder, and the numbers that pace the day. Fetched once at launch.
 */
class ShowCatalogueController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => CategoryResource::collection(
                Category::where('is_active', true)->orderBy('sort_order')->get()
            ),
            'levels' => LevelLadder::ladder()->values()->map(fn ($level, $i) => [
                'level' => $i + 1,
                'name' => $level->name,
                'icon' => $level->icon,
                'minXp' => $level->min_xp,
            ]),
            // Keyed in camelCase like every other payload. The enum spells these in
            // snake_case because that is what the column holds; what leaves here is
            // the app's own vocabulary — `dailyFreeEntries`, not `daily_free_entries`.
            'limits' => collect(AppSettingKey::cases())
                ->mapWithKeys(fn (AppSettingKey $key) => [Str::camel($key->value) => AppSetting::number($key)]),
        ]);
    }
}
