<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\ShowLoginController;
use App\Http\Controllers\Admin\Badges\DestroyBadgeController;
use App\Http\Controllers\Admin\Badges\IndexBadgesController;
use App\Http\Controllers\Admin\Badges\StoreBadgeController;
use App\Http\Controllers\Admin\Badges\UpdateBadgeController;
use App\Http\Controllers\Admin\Categories\DestroyCategoryController;
use App\Http\Controllers\Admin\Categories\IndexCategoriesController;
use App\Http\Controllers\Admin\Categories\StoreCategoryController;
use App\Http\Controllers\Admin\Categories\UpdateCategoryController;
use App\Http\Controllers\Admin\Chapters\DestroyChapterController;
use App\Http\Controllers\Admin\Chapters\IndexChaptersController;
use App\Http\Controllers\Admin\Chapters\StoreChapterController;
use App\Http\Controllers\Admin\Chapters\UpdateChapterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Families\IndexChildrenController;
use App\Http\Controllers\Admin\Families\IndexGiftsController;
use App\Http\Controllers\Admin\Families\ResetGiftController;
use App\Http\Controllers\Admin\Families\ShowChildController;
use App\Http\Controllers\Admin\Levels\DestroyLevelController;
use App\Http\Controllers\Admin\Levels\IndexLevelsController;
use App\Http\Controllers\Admin\Levels\StoreLevelController;
use App\Http\Controllers\Admin\Levels\UpdateLevelController;
use App\Http\Controllers\Admin\Prompts\DestroyPromptController;
use App\Http\Controllers\Admin\Prompts\IndexPromptsController;
use App\Http\Controllers\Admin\Prompts\StorePromptController;
use App\Http\Controllers\Admin\Prompts\UpdatePromptController;
use App\Http\Controllers\Admin\Settings\ShowSettingsController;
use App\Http\Controllers\Admin\Settings\UpdateSettingsController;
use App\Http\Controllers\Admin\Steps\DestroyStepController;
use App\Http\Controllers\Admin\Steps\IndexStepsController;
use App\Http\Controllers\Admin\Steps\StoreStepController;
use App\Http\Controllers\Admin\Steps\UpdateStepController;
use App\Http\Controllers\Admin\Users\IndexUsersController;
use App\Http\Controllers\Admin\Users\RestoreUserController;
use App\Http\Controllers\Admin\Users\ShowUserController;
use App\Http\Controllers\Admin\Users\UpdateUserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', ShowLoginController::class)->name('admin.login');
        Route::post('login', LoginController::class);
    });

    Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
        Route::post('logout', LogoutController::class);

        Route::get('/', DashboardController::class)->name('admin.dashboard');

        Route::get('chapters', IndexChaptersController::class)->name('admin.chapters');
        Route::post('chapters', StoreChapterController::class);
        Route::put('chapters/{chapter}', UpdateChapterController::class);
        Route::delete('chapters/{chapter}', DestroyChapterController::class);

        Route::get('steps', IndexStepsController::class)->name('admin.steps');
        Route::post('steps', StoreStepController::class);
        Route::put('steps/{step}', UpdateStepController::class);
        Route::delete('steps/{step}', DestroyStepController::class);

        Route::get('categories', IndexCategoriesController::class)->name('admin.categories');
        Route::post('categories', StoreCategoryController::class);
        Route::put('categories/{category}', UpdateCategoryController::class);
        Route::delete('categories/{category}', DestroyCategoryController::class);

        Route::get('badges', IndexBadgesController::class)->name('admin.badges');
        Route::post('badges', StoreBadgeController::class);
        Route::put('badges/{badge}', UpdateBadgeController::class);
        Route::delete('badges/{badge}', DestroyBadgeController::class);

        Route::get('levels', IndexLevelsController::class)->name('admin.levels');
        Route::post('levels', StoreLevelController::class);
        Route::put('levels/{level}', UpdateLevelController::class);
        Route::delete('levels/{level}', DestroyLevelController::class);

        Route::get('prompts', IndexPromptsController::class)->name('admin.prompts');
        Route::post('prompts', StorePromptController::class);
        Route::put('prompts/{prompt}', UpdatePromptController::class);
        Route::delete('prompts/{prompt}', DestroyPromptController::class);

        Route::get('settings', ShowSettingsController::class)->name('admin.settings');
        Route::put('settings', UpdateSettingsController::class);

        Route::get('users', IndexUsersController::class)->name('admin.users');
        Route::get('users/{user}', ShowUserController::class);
        Route::put('users/{user}', UpdateUserController::class);
        Route::post('users/{user}/restore', RestoreUserController::class);

        Route::get('children', IndexChildrenController::class)->name('admin.children');
        Route::get('children/{child}', ShowChildController::class);

        Route::get('gifts', IndexGiftsController::class)->name('admin.gifts');
        Route::post('gifts/{gift}/reset', ResetGiftController::class);
    });
});
