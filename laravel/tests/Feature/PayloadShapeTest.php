<?php

declare(strict_types=1);

/** Every key in a payload, however deep. */
function payloadKeys(mixed $node): array
{
    $found = [];

    if (is_array($node)) {
        foreach ($node as $key => $value) {
            if (is_string($key)) {
                $found[] = $key;
            }

            $found = array_merge($found, payloadKeys($value));
        }
    }

    return $found;
}

/**
 * Responses speak camelCase, requests speak snake_case. The asymmetry is
 * deliberate — requests mirror the columns, responses mirror the app's types —
 * and it only holds if nothing leaks an enum value or a column name on the way out.
 */
it('never spells a response key the way the column does', function () {
    [, $child] = family(ageMonths: 24);

    $calls = [
        'auth/me' => $this->getJson('/api/v1/auth/me'),
        'catalogue' => $this->getJson('/api/v1/catalogue'),
        'children' => $this->getJson('/api/v1/children'),
        'child' => $this->getJson("/api/v1/children/{$child->id}"),
        'chapters' => $this->getJson("/api/v1/children/{$child->id}/chapters"),
        'entries' => $this->getJson("/api/v1/children/{$child->id}/entries"),
        'progress' => $this->getJson("/api/v1/children/{$child->id}/progress"),
        'growth' => $this->getJson("/api/v1/children/{$child->id}/growth"),
        'rewards' => $this->getJson("/api/v1/children/{$child->id}/rewards"),
    ];

    foreach ($calls as $label => $response) {
        $snake = collect(payloadKeys($response->assertOk()->json()))
            ->unique()
            ->filter(fn (string $key) => (bool) preg_match('/[a-z0-9]_[a-z]/', $key))
            ->values()
            ->all();

        expect($snake)->toBe([], "{$label} leaked a snake_case key");
    }
});

/** A row that can be acted on carries an abilities object, never loose booleans. */
it('grants every action through abilities', function () {
    [, $child] = family(ageMonths: 24);

    $map = $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk();

    foreach ([
        'child' => $this->getJson("/api/v1/children/{$child->id}")->json('data'),
        'chapter' => $map->json('data.0'),
        'milestone' => $map->json('data.0.milestones.0'),
    ] as $row) {
        // `isEditable` is not in this list on purpose: despite the name it records
        // where a row came from, catalogue or parent, and grants nothing.
        expect($row)->toHaveKey('abilities')
            ->and($row['abilities'])->toBeArray()
            ->and(array_keys($row))->not->toContain('canContribute')
            ->and(array_keys($row))->not->toContain('isDeletable');
    }
});
