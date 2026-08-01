<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    protected $model = Child::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'created_by_user_id' => User::factory(),
            'name' => fake()->firstName(),
            'birthday' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'gender' => fake()->randomElement(Gender::cases()),
            'xp' => 0,
        ];
    }

    public function bornMonthsAgo(int $months): self
    {
        return $this->state(fn () => [
            'birthday' => now()->subMonths($months)->toDateString(),
        ]);
    }
}
