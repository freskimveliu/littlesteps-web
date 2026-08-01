<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\DeleteAccountController;
use App\Http\Controllers\Api\V1\Auth\GuestController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\UpdateProfileController;
use App\Http\Controllers\Api\V1\Catalogue\ShowCatalogueController;
use App\Http\Controllers\Api\V1\Catalogue\ShowPromptController;
use App\Http\Controllers\Api\V1\Chapters\CompleteChapterController;
use App\Http\Controllers\Api\V1\Chapters\DestroyChapterController;
use App\Http\Controllers\Api\V1\Chapters\IndexChaptersController;
use App\Http\Controllers\Api\V1\Chapters\ReorderChaptersController;
use App\Http\Controllers\Api\V1\Chapters\ReorderMilestonesController;
use App\Http\Controllers\Api\V1\Chapters\StoreChapterController;
use App\Http\Controllers\Api\V1\Chapters\UpdateChapterController;
use App\Http\Controllers\Api\V1\Children\DestroyChildController;
use App\Http\Controllers\Api\V1\Children\IndexChildrenController;
use App\Http\Controllers\Api\V1\Children\ShowChildController;
use App\Http\Controllers\Api\V1\Children\StoreChildController;
use App\Http\Controllers\Api\V1\Children\UpdateChildController;
use App\Http\Controllers\Api\V1\Devices\DestroyDeviceController;
use App\Http\Controllers\Api\V1\Devices\StoreDeviceController;
use App\Http\Controllers\Api\V1\Entries\DestroyEntryController;
use App\Http\Controllers\Api\V1\Entries\IndexEntriesController;
use App\Http\Controllers\Api\V1\Entries\StoreEntryController;
use App\Http\Controllers\Api\V1\Entries\UpdateEntryController;
use App\Http\Controllers\Api\V1\Media\DestroyEntryMediaController;
use App\Http\Controllers\Api\V1\Media\StoreChildPhotoController;
use App\Http\Controllers\Api\V1\Media\StoreEntryMediaController;
use App\Http\Controllers\Api\V1\Media\StoreUserPhotoController;
use App\Http\Controllers\Api\V1\Milestones\DestroyMilestoneController;
use App\Http\Controllers\Api\V1\Milestones\HideMilestoneController;
use App\Http\Controllers\Api\V1\Milestones\StoreMilestoneController;
use App\Http\Controllers\Api\V1\Milestones\UpdateMilestoneController;
use App\Http\Controllers\Api\V1\Progress\ClaimRewardController;
use App\Http\Controllers\Api\V1\Progress\IndexRewardsController;
use App\Http\Controllers\Api\V1\Progress\ShowGrowthController;
use App\Http\Controllers\Api\V1\Progress\ShowProgressController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/guest', GuestController::class);
    Route::post('auth/login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/register', RegisterController::class);
        Route::post('auth/logout', LogoutController::class);
        Route::get('auth/me', MeController::class);
        Route::patch('auth/me', UpdateProfileController::class);
        Route::delete('auth/me', DeleteAccountController::class);
        Route::post('auth/me/photo', StoreUserPhotoController::class);

        Route::get('catalogue', ShowCatalogueController::class);

        Route::get('children', IndexChildrenController::class);
        Route::post('children', StoreChildController::class);
        Route::get('children/{child}', ShowChildController::class);
        Route::patch('children/{child}', UpdateChildController::class);
        Route::delete('children/{child}', DestroyChildController::class);
        Route::post('children/{child}/photo', StoreChildPhotoController::class);

        Route::get('children/{child}/chapters', IndexChaptersController::class);
        Route::post('children/{child}/chapters', StoreChapterController::class);
        Route::post('children/{child}/chapters/reorder', ReorderChaptersController::class);
        Route::patch('children/{child}/chapters/{chapter}', UpdateChapterController::class);
        Route::delete('children/{child}/chapters/{chapter}', DestroyChapterController::class);
        Route::post('children/{child}/chapters/{chapter}/complete', CompleteChapterController::class);
        Route::post('children/{child}/chapters/{chapter}/reorder', ReorderMilestonesController::class);

        Route::post('children/{child}/milestones', StoreMilestoneController::class);
        Route::patch('children/{child}/milestones/{milestone}', UpdateMilestoneController::class);
        Route::delete('children/{child}/milestones/{milestone}', DestroyMilestoneController::class);
        Route::post('children/{child}/milestones/{milestone}/hide', HideMilestoneController::class);

        Route::get('children/{child}/entries', IndexEntriesController::class);
        Route::post('children/{child}/entries', StoreEntryController::class);
        Route::patch('children/{child}/entries/{entry}', UpdateEntryController::class);
        Route::delete('children/{child}/entries/{entry}', DestroyEntryController::class);
        Route::post('children/{child}/entries/{entry}/media', StoreEntryMediaController::class);
        Route::delete('children/{child}/entries/{entry}/media/{media}', DestroyEntryMediaController::class);

        Route::get('children/{child}/progress', ShowProgressController::class);
        Route::get('children/{child}/growth', ShowGrowthController::class);
        Route::get('children/{child}/prompt', ShowPromptController::class);
        Route::get('children/{child}/rewards', IndexRewardsController::class);
        Route::post('children/{child}/rewards/{reward}/claim', ClaimRewardController::class);

        Route::post('devices', StoreDeviceController::class);
        Route::delete('devices', DestroyDeviceController::class);
    });
});
