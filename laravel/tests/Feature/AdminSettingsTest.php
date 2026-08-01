<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Enums\Mood;
use App\Models\AppSetting;
use App\Models\User;

beforeEach(fn () => test()->withoutVite());

function asAdmin(): User
{
    $admin = User::factory()->create(['is_admin' => true]);

    test()->actingAs($admin);

    return $admin;
}

it('retunes a number the app paces itself by', function () {
    asAdmin();

    $this->put('/admin/settings', [
        'settings' => [AppSettingKey::DailyFreeEntries->value => 3],
    ])->assertRedirect();

    expect(AppSetting::number(AppSettingKey::DailyFreeEntries))->toBe(3);
});

it('changes what a parent can do that same day', function () {
    asAdmin();

    $this->put('/admin/settings', [
        'settings' => [AppSettingKey::DailyFreeEntries->value => 2],
    ])->assertRedirect();

    [, $child] = family();

    foreach (range(1, 2) as $i) {
        $this->postJson("/api/v1/children/{$child->id}/entries", [
            'description' => "Memory number {$i}.",
            'date' => now()->toDateString(),
            'mood' => Mood::Joyful->value,
        ])->assertCreated();
    }

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'One too many.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
    ])->assertJsonValidationErrorFor('date');
});

it('refuses a setting outside the range the app can hold', function () {
    asAdmin();

    $this->put('/admin/settings', ['settings' => [AppSettingKey::DailyFreeEntries->value => 1001]])
        ->assertSessionHasErrors('settings.'.AppSettingKey::DailyFreeEntries->value);

    $this->put('/admin/settings', ['settings' => [AppSettingKey::DailyFreeEntries->value => -1]])
        ->assertSessionHasErrors('settings.'.AppSettingKey::DailyFreeEntries->value);

    expect(AppSetting::number(AppSettingKey::DailyFreeEntries))
        ->toBe(AppSettingKey::DailyFreeEntries->default());
});

it('quietly ignores a setting it does not recognise', function () {
    asAdmin();

    $this->put('/admin/settings', ['settings' => ['free_puppies_per_day' => 5]])->assertRedirect();

    expect(AppSetting::where('key', 'free_puppies_per_day')->exists())->toBeFalse();
});

it('keeps the settings screen away from a parent', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->put('/admin/settings', ['settings' => [AppSettingKey::DailyFreeEntries->value => 99]])
        ->assertForbidden();

    expect(AppSetting::number(AppSettingKey::DailyFreeEntries))
        ->toBe(AppSettingKey::DailyFreeEntries->default());
});
