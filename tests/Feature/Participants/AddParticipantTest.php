<?php

namespace Tests\Feature\Participants;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_add_a_ghost_participant(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $trip = Trip::factory()->ownedBy($user)->create();

        $response = $this->actingAs($user)->postJson("/api/trips/{$trip->id}/participants", [
            'name' => 'Ghost Guest',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Ghost Guest')
            ->assertJsonPath('data.user_id', null)
            ->assertJsonPath('data.is_ghost', true)
            ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('participants', [
            'trip_id' => $trip->id,
            'name' => 'Ghost Guest',
            'user_id' => null,
            'active' => true,
        ]);
    }

    public function test_a_non_member_cannot_add_a_participant(): void
    {
        $trip = Trip::factory()->ownedBy(User::factory()->create())->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->postJson("/api/trips/{$trip->id}/participants", ['name' => 'Nope'])
            ->assertForbidden();

        $this->assertDatabaseMissing('participants', ['name' => 'Nope']);
    }

    public function test_it_requires_a_name(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $trip = Trip::factory()->ownedBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/trips/{$trip->id}/participants", [])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('name');
    }
}
