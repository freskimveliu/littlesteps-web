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
use App\Http\Controllers\Api\V1\Media\DestroyEntryPhotoController;
use App\Http\Controllers\Api\V1\Media\StoreChildPhotoController;
use App\Http\Controllers\Api\V1\Media\StoreEntryPhotoController;
use App\Http\Controllers\Api\V1\Media\StoreUserPhotoController;
use App\Http\Controllers\Api\V1\Milestones\CompleteMilestoneController;
use App\Http\Controllers\Api\V1\Milestones\HideMilestoneController;
use App\Http\Controllers\Api\V1\Milestones\IndexMilestonesController;
use App\Http\Controllers\Api\V1\Progress\ClaimRewardController;
use App\Http\Controllers\Api\V1\Progress\IndexRewardsController;
use App\Http\Controllers\Api\V1\Progress\ShowGrowthController;
use App\Http\Controllers\Api\V1\Progress\ShowProgressController;
use App\Http\Controllers\Api\V1\Steps\DestroyStepController;
use App\Http\Controllers\Api\V1\Steps\HideStepController;
use App\Http\Controllers\Api\V1\Steps\StoreStepController;
use App\Http\Controllers\Api\V1\Steps\UpdateStepController;
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

        Route::get('children/{child}/milestones', IndexMilestonesController::class);
        Route::post('children/{child}/milestones/{milestone}/complete', CompleteMilestoneController::class);
        Route::post('children/{child}/milestones/{milestone}/hide', HideMilestoneController::class);

        Route::post('children/{child}/steps', StoreStepController::class);
        Route::patch('children/{child}/steps/{step}', UpdateStepController::class);
        Route::delete('children/{child}/steps/{step}', DestroyStepController::class);
        Route::post('children/{child}/steps/{step}/hide', HideStepController::class);

        Route::get('children/{child}/entries', IndexEntriesController::class);
        Route::post('children/{child}/entries', StoreEntryController::class);
        Route::patch('children/{child}/entries/{entry}', UpdateEntryController::class);
        Route::delete('children/{child}/entries/{entry}', DestroyEntryController::class);
        Route::post('children/{child}/entries/{entry}/photos', StoreEntryPhotoController::class);
        Route::delete('children/{child}/entries/{entry}/photos/{media}', DestroyEntryPhotoController::class);

        Route::get('children/{child}/progress', ShowProgressController::class);
        Route::get('children/{child}/growth', ShowGrowthController::class);
        Route::get('children/{child}/prompt', ShowPromptController::class);
        Route::get('children/{child}/rewards', IndexRewardsController::class);
        Route::post('children/{child}/rewards/{reward}/claim', ClaimRewardController::class);

        Route::post('devices', StoreDeviceController::class);
        Route::delete('devices', DestroyDeviceController::class);
    });
});
