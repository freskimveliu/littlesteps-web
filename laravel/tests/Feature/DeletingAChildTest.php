<?php

declare(strict_types=1);

use App\Enums\Mood;

it('lets an empty adventure go without ceremony', function () {
    [, $child] = family();

    $this->deleteJson("/api/v1/children/{$child->id}")->assertNoContent();
});

it('will not delete an album holding memories without the name typed back', function () {
    [, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She found her feet today.',
        'date' => now()->toDateString(),
        'mood' => Mood::Proud->value,
    ])->assertCreated();

    $this->deleteJson("/api/v1/children/{$child->id}")
        ->assertJsonValidationErrorFor('confirm');

    $this->deleteJson("/api/v1/children/{$child->id}", ['confirm' => 'Something else'])
        ->assertJsonValidationErrorFor('confirm');

    $this->deleteJson("/api/v1/children/{$child->id}", ['confirm' => $child->name])
        ->assertNoContent();
});

it('tells a stranger nothing about whether the album has anything in it', function () {
    [, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'A quiet afternoon.',
        'date' => now()->toDateString(),
        'mood' => Mood::Tender->value,
    ])->assertCreated();

    $this->actingAs(viewer($child), 'sanctum')
        ->deleteJson("/api/v1/children/{$child->id}")
        ->assertForbidden();
});
