<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalogue;

use App\Enums\AppSettingKey;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppSetting;
use App\Models\Category;
use App\Support\Progress\Level;
use Illuminate\Http\JsonResponse;

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
            'levels' => Level::ladder()->values()->map(fn ($level, $i) => [
                'level' => $i + 1,
                'name' => $level->name,
                'icon' => $level->icon,
                'minXp' => $level->min_xp,
            ]),
            'limits' => collect(AppSettingKey::cases())
                ->mapWithKeys(fn (AppSettingKey $key) => [$key->value => AppSetting::number($key)]),
        ]);
    }
}
