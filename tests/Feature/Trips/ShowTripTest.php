<?php

namespace Tests\Feature\Trips;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_view_the_trip_with_members_and_participants(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $trip = Trip::factory()->ownedBy($user)->create();
        $trip->participants()->create(['name' => 'Ghost Guest', 'user_id' => null, 'active' => true]);

        $response = $this->actingAs($user)->getJson("/api/trips/{$trip->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.role', 'owner')
            ->assertJsonCount(1, 'data.members')
            ->assertJsonPath('data.members.0.name', 'Ada')
            ->assertJsonCount(2, 'data.participants'); // owner + ghost
    }

    public function test_a_non_member_cannot_view_the_trip(): void
    {
        $trip = Trip::factory()->ownedBy(User::factory()->create())->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->getJson("/api/trips/{$trip->id}")->assertForbidden();
    }

    public function test_an_unknown_trip_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/trips/999999')->assertNotFound();
    }

    public function test_it_requires_authentication(): void
    {
        $trip = Trip::factory()->ownedBy(User::factory()->create())->create();

        $this->getJson("/api/trips/{$trip->id}")->assertUnauthorized();
    }
}
