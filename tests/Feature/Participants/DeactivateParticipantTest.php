<?php

namespace Tests\Feature\Participants;

use App\Models\Participant;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivateParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_deactivate_a_participant(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $trip = Trip::factory()->ownedBy($user)->create();
        $ghost = $trip->participants()->create(['name' => 'Ghost', 'user_id' => null, 'active' => true]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/trips/{$trip->id}/participants/{$ghost->id}");

        // Deactivate, never destroy: the row survives with active=false so the
        // client can reflect it and history stays intact.
        $response->assertOk()
            ->assertJsonPath('data.id', $ghost->id)
            ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('participants', ['id' => $ghost->id, 'active' => false]);
    }

    public function test_a_participant_from_another_trip_is_not_reachable(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $myTrip = Trip::factory()->ownedBy($user)->create();
        $otherTrip = Trip::factory()->ownedBy(User::factory()->create())->create();
        $otherParticipant = $otherTrip->participants()->create(['name' => 'Elsewhere', 'user_id' => null]);

        // Scoped binding: the participant doesn't belong to {myTrip} → 404,
        // not a cross-trip mutation.
        $this->actingAs($user)
            ->deleteJson("/api/trips/{$myTrip->id}/participants/{$otherParticipant->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('participants', ['id' => $otherParticipant->id, 'active' => true]);
    }

    public function test_a_non_member_cannot_deactivate_a_participant(): void
    {
        $trip = Trip::factory()->ownedBy(User::factory()->create())->create();
        $ghost = $trip->participants()->create(['name' => 'Ghost', 'user_id' => null, 'active' => true]);
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->deleteJson("/api/trips/{$trip->id}/participants/{$ghost->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('participants', ['id' => $ghost->id, 'active' => true]);
    }
}
