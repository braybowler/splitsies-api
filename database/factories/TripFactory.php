<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\TripRole;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' trip',
            'base_currency' => Currency::USD,
            'owner_id' => User::factory(),
            'starts_on' => null,
            'ends_on' => null,
        ];
    }

    /**
     * A fully-seated trip: the given user is its owner, owner-member, and a
     * participant — mirroring what create-trip produces.
     */
    public function ownedBy(User $user): static
    {
        return $this
            ->for($user, 'owner')
            ->afterCreating(function (Trip $trip) use ($user): void {
                $trip->members()->create([
                    'user_id' => $user->id,
                    'role' => TripRole::Owner,
                ]);
                $trip->participants()->create([
                    'name' => $user->name ?? 'Owner',
                    'user_id' => $user->id,
                    'active' => true,
                ]);
            });
    }
}
