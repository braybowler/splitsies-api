<?php

namespace Tests\Feature\Auth;

use App\Models\MagicLinkToken;
use App\Models\User;
use Database\Factories\MagicLinkTokenFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RedeemMagicLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_token_logs_the_user_in_and_returns_a_bearer_token(): void
    {
        [$raw, $token] = $this->makeToken();

        $response = $this->postJson('/api/auth/redeem', ['token' => $raw]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.id', $token->user_id);

        // The returned bearer token must actually authenticate a request.
        $this->withToken($response->json('token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $token->user_id);
    }

    public function test_redeeming_marks_the_token_used_and_verifies_the_email(): void
    {
        [$raw, $token] = $this->makeToken();
        $this->assertNull($token->user->email_verified_at);

        $this->postJson('/api/auth/redeem', ['token' => $raw])->assertOk();

        $this->assertNotNull($token->fresh()->used_at, 'Redeemed token should be marked used.');
        $this->assertNotNull($token->user->fresh()->email_verified_at, 'Clicking the link proves ownership.');
    }

    public function test_a_token_can_only_be_redeemed_once(): void
    {
        [$raw] = $this->makeToken();

        $this->postJson('/api/auth/redeem', ['token' => $raw])->assertOk();

        // Single-use: the second redemption must fail and issue no new session.
        $this->postJson('/api/auth/redeem', ['token' => $raw])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This login link is invalid or has expired.');

        $this->assertSame(1, \DB::table('personal_access_tokens')->count());
    }

    public function test_an_expired_token_is_rejected(): void
    {
        [$raw] = $this->makeToken(fn ($factory) => $factory->expired());

        $this->postJson('/api/auth/redeem', ['token' => $raw])->assertStatus(422);
        $this->assertSame(0, \DB::table('personal_access_tokens')->count());
    }

    public function test_an_already_used_token_is_rejected(): void
    {
        [$raw] = $this->makeToken(fn ($factory) => $factory->used());

        $this->postJson('/api/auth/redeem', ['token' => $raw])->assertStatus(422);
    }

    public function test_an_unknown_token_is_rejected(): void
    {
        $this->postJson('/api/auth/redeem', ['token' => Str::random(64)])
            ->assertStatus(422);
    }

    public function test_it_requires_a_token(): void
    {
        $this->postJson('/api/auth/redeem', [])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('token');
    }

    /**
     * Create a persisted magic-link token and return its raw value alongside
     * the model, mirroring how the raw token only ever exists client-side.
     *
     * @param  (callable(MagicLinkTokenFactory): MagicLinkTokenFactory)|null  $customise
     * @return array{0: string, 1: MagicLinkToken}
     */
    private function makeToken(?callable $customise = null): array
    {
        $raw = Str::random(64);
        $user = User::factory()->create(['email_verified_at' => null]);

        $factory = MagicLinkToken::factory()
            ->for($user)
            ->state(['token_hash' => hash('sha256', $raw)]);

        if ($customise !== null) {
            $factory = $customise($factory);
        }

        return [$raw, $factory->create()];
    }
}
