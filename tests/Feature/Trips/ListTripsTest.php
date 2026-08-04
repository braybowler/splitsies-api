<?php

namespace Tests\Feature\Trips;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTripsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_trips_the_user_is_a_member_of(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $mine = Trip::factory()->ownedBy($user)->create(['name' => 'Mine']);

        // A trip owned by someone else must not appear.
        Trip::factory()->ownedBy(User::factory()->create())->create(['name' => 'Theirs']);

        $response = $this->actingAs($user)->getJson('/api/trips');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.role', 'owner');
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/trips')->assertUnauthorized();
    }
}
