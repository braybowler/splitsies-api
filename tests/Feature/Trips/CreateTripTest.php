<?php

namespace Tests\Feature\Trips;

use App\Enums\TripRole;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_trip_seating_the_creator_as_owner_and_participant(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);

        $response = $this->actingAs($user)->postJson('/api/trips', [
            'name' => 'Iceland 2026',
            'base_currency' => 'EUR',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Iceland 2026')
            ->assertJsonPath('data.base_currency', 'EUR')
            ->assertJsonPath('data.role', 'owner');

        $trip = Trip::sole();
        $this->assertSame($user->id, $trip->owner_id);

        // Exactly one owner-member and one participant carrying the creator's id.
        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'role' => TripRole::Owner->value,
        ]);
        $this->assertDatabaseHas('participants', [
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'name' => 'Ada',
            'active' => true,
        ]);
        $this->assertSame(1, $trip->members()->count());
        $this->assertSame(1, $trip->participants()->count());
    }

    public function test_display_name_backfills_a_creator_with_no_name(): void
    {
        // The Auth slice defers name capture; trip creation is where a nameless
        // creator finally provides one, and we persist it to their account.
        $user = User::factory()->create(['name' => null]);

        $this->actingAs($user)->postJson('/api/trips', [
            'name' => 'Road Trip',
            'base_currency' => 'USD',
            'display_name' => 'Grace',
        ])->assertCreated();

        $this->assertSame('Grace', $user->fresh()->name);
        $this->assertDatabaseHas('participants', ['user_id' => $user->id, 'name' => 'Grace']);
    }

    public function test_display_name_does_not_overwrite_an_existing_account_name(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);

        $this->actingAs($user)->postJson('/api/trips', [
            'name' => 'Trip',
            'base_currency' => 'USD',
            'display_name' => 'Something Else',
        ])->assertCreated();

        $this->assertSame('Ada', $user->fresh()->name);
    }

    public function test_a_nameless_creator_must_supply_a_display_name(): void
    {
        $user = User::factory()->create(['name' => null]);

        $this->actingAs($user)->postJson('/api/trips', [
            'name' => 'Trip',
            'base_currency' => 'USD',
        ])->assertStatus(422)->assertJsonValidationErrorFor('display_name');
    }

    public function test_it_rejects_an_unsupported_currency(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);

        $this->actingAs($user)->postJson('/api/trips', [
            'name' => 'Trip',
            'base_currency' => 'XYZ',
        ])->assertStatus(422)->assertJsonValidationErrorFor('base_currency');
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/trips', [
            'name' => 'Trip',
            'base_currency' => 'USD',
        ])->assertUnauthorized();
    }

    public function test_a_user_can_be_a_participant_at_most_once_per_trip(): void
    {
        // DB-enforced guard against a double-counted person. Ghosts (null
        // user_id) are exempt because MySQL treats NULLs as distinct.
        $user = User::factory()->create(['name' => 'Ada']);
        $trip = Trip::factory()->ownedBy($user)->create();

        $this->expectException(QueryException::class);

        $trip->participants()->create(['name' => 'Ada again', 'user_id' => $user->id]);
    }

    public function test_a_user_can_be_a_member_at_most_once_per_trip(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $trip = Trip::factory()->ownedBy($user)->create();

        $this->expectException(QueryException::class);

        $trip->members()->create(['user_id' => $user->id, 'role' => TripRole::Member]);
    }
}
