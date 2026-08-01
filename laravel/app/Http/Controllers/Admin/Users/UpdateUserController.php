<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateUserController extends Controller
{
    public function __invoke(Request $request, int $user): RedirectResponse
    {
        $parent = User::withTrashed()->findOrFail($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($parent->id)],
            'timezone' => ['required', 'string', 'timezone'],
            'is_admin' => ['boolean'],
        ]);

        // The last admin cannot lock everyone out of this console.
        if ($parent->is_admin && ! ($validated['is_admin'] ?? false) && User::where('is_admin', true)->count() === 1) {
            return back()->with('error', 'This is the only admin left. Promote someone else first.');
        }

        $parent->forceFill($validated)->save();

        return back()->with('success', 'Profile saved.');
    }
}
