<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Support\Admin\UserSummary;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserProfileController extends Controller
{
    public function __invoke(int $user): Response
    {
        $parent = UserSummary::find($user);

        return Inertia::render('Admin/Users/Profile', [
            'user' => UserSummary::for($parent),
            'devices' => $parent->devices()->get(),
            'settings' => $parent->settingsMap(),
        ]);
    }
}
