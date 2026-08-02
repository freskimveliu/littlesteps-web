<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\ShowLoginController;
use App\Http\Controllers\Admin\Categories\DestroyCategoryController;
use App\Http\Controllers\Admin\Categories\IndexCategoriesController;
use App\Http\Controllers\Admin\Categories\StoreCategoryController;
use App\Http\Controllers\Admin\Categories\UpdateCategoryController;
use App\Http\Controllers\Admin\Chapters\DestroyChapterController;
use App\Http\Controllers\Admin\Chapters\IndexChaptersController;
use App\Http\Controllers\Admin\Chapters\StoreChapterController;
use App\Http\Controllers\Admin\Chapters\UpdateChapterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Families\DestroyChildController;
use App\Http\Controllers\Admin\Families\IndexChildrenController;
use App\Http\Controllers\Admin\Families\IndexGiftsController;
use App\Http\Controllers\Admin\Families\ResetGiftController;
use App\Http\Controllers\Admin\Families\ShowChildController;
use App\Http\Controllers\Admin\Families\ShowChildFamilyController;
use App\Http\Controllers\Admin\Families\ShowChildGiftsController;
use App\Http\Controllers\Admin\Families\ShowChildMemoriesController;
use App\Http\Controllers\Admin\Families\ShowChildTrophiesController;
use App\Http\Controllers\Admin\Families\UpdateChildController;
use App\Http\Controllers\Admin\Levels\DestroyLevelController;
use App\Http\Controllers\Admin\Levels\IndexLevelsController;
use App\Http\Controllers\Admin\Levels\StoreLevelController;
use App\Http\Controllers\Admin\Levels\UpdateLevelController;
use App\Http\Controllers\Admin\Milestones\DestroyMilestoneController;
use App\Http\Controllers\Admin\Milestones\IndexMilestonesController;
use App\Http\Controllers\Admin\Milestones\StoreMilestoneController;
use App\Http\Controllers\Admin\Milestones\UpdateMilestoneController;
use App\Http\Controllers\Admin\Prompts\DestroyPromptController;
use App\Http\Controllers\Admin\Prompts\IndexPromptsController;
use App\Http\Controllers\Admin\Prompts\StorePromptController;
use App\Http\Controllers\Admin\Prompts\UpdatePromptController;
use App\Http\Controllers\Admin\Settings\ShowSettingsController;
use App\Http\Controllers\Admin\Settings\UpdateSettingsController;
use App\Http\Controllers\Admin\Trophies\DestroyTrophyController;
use App\Http\Controllers\Admin\Trophies\IndexTrophiesController;
use App\Http\Controllers\Admin\Trophies\StoreTrophyController;
use App\Http\Controllers\Admin\Trophies\UpdateTrophyController;
use App\Http\Controllers\Admin\Users\IndexUsersController;
use App\Http\Controllers\Admin\Users\RestoreUserController;
use App\Http\Controllers\Admin\Users\ShowUserChildrenController;
use App\Http\Controllers\Admin\Users\ShowUserController;
use App\Http\Controllers\Admin\Users\ShowUserProfileController;
use App\Http\Controllers\Admin\Users\StoreUserController;
use App\Http\Controllers\Admin\Users\UpdateUserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', ShowLoginController::class)->name('login');
        Route::post('login', LoginController::class)->name('login.store');
    });

    // Signing out only needs a session, not the admin gate — otherwise someone
    // who is signed in but not an admin lands on a 403 with no way back out.
    Route::post('logout', LogoutController::class)->middleware('auth')->name('logout');

    Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('chapters', IndexChaptersController::class)->name('chapters.index');
        Route::post('chapters', StoreChapterController::class)->name('chapters.store');
        Route::put('chapters/{chapter}', UpdateChapterController::class)->name('chapters.update');
        Route::delete('chapters/{chapter}', DestroyChapterController::class)->name('chapters.destroy');

        Route::get('milestones', IndexMilestonesController::class)->name('milestones.index');
        Route::post('milestones', StoreMilestoneController::class)->name('milestones.store');
        Route::put('milestones/{milestone}', UpdateMilestoneController::class)->name('milestones.update');
        Route::delete('milestones/{milestone}', DestroyMilestoneController::class)->name('milestones.destroy');

        Route::get('categories', IndexCategoriesController::class)->name('categories.index');
        Route::post('categories', StoreCategoryController::class)->name('categories.store');
        Route::put('categories/{category}', UpdateCategoryController::class)->name('categories.update');
        Route::delete('categories/{category}', DestroyCategoryController::class)->name('categories.destroy');

        Route::get('trophies', IndexTrophiesController::class)->name('trophies.index');
        Route::post('trophies', StoreTrophyController::class)->name('trophies.store');
        Route::put('trophies/{trophy}', UpdateTrophyController::class)->name('trophies.update');
        Route::delete('trophies/{trophy}', DestroyTrophyController::class)->name('trophies.destroy');

        Route::get('levels', IndexLevelsController::class)->name('levels.index');
        Route::post('levels', StoreLevelController::class)->name('levels.store');
        Route::put('levels/{level}', UpdateLevelController::class)->name('levels.update');
        Route::delete('levels/{level}', DestroyLevelController::class)->name('levels.destroy');

        Route::get('prompts', IndexPromptsController::class)->name('prompts.index');
        Route::post('prompts', StorePromptController::class)->name('prompts.store');
        Route::put('prompts/{prompt}', UpdatePromptController::class)->name('prompts.update');
        Route::delete('prompts/{prompt}', DestroyPromptController::class)->name('prompts.destroy');

        Route::get('settings', ShowSettingsController::class)->name('settings.show');
        Route::put('settings', UpdateSettingsController::class)->name('settings.update');

        Route::get('users', IndexUsersController::class)->name('users.index');
        Route::post('users', StoreUserController::class)->name('users.store');
        Route::get('users/{user}', ShowUserController::class)->name('users.show');
        Route::get('users/{user}/children', ShowUserChildrenController::class)->name('users.children');
        Route::get('users/{user}/profile', ShowUserProfileController::class)->name('users.profile');
        Route::put('users/{user}', UpdateUserController::class)->name('users.update');
        Route::post('users/{user}/restore', RestoreUserController::class)->name('users.restore');

        Route::get('children', IndexChildrenController::class)->name('children.index');
        Route::get('children/{child}', ShowChildController::class)->name('children.show');
        Route::get('children/{child}/memories', ShowChildMemoriesController::class)->name('children.memories');
        Route::get('children/{child}/trophies', ShowChildTrophiesController::class)->name('children.trophies');
        Route::get('children/{child}/gifts', ShowChildGiftsController::class)->name('children.gifts');
        Route::get('children/{child}/family', ShowChildFamilyController::class)->name('children.family');
        Route::put('children/{child}', UpdateChildController::class)->name('children.update');
        Route::delete('children/{child}', DestroyChildController::class)->name('children.destroy');

        Route::get('gifts', IndexGiftsController::class)->name('gifts.index');
        Route::post('gifts/{gift}/reset', ResetGiftController::class)->name('gifts.reset');
    });
});
