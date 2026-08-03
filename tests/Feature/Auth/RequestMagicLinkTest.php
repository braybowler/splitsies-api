<?php

namespace Tests\Feature\Auth;

use App\Mail\MagicLinkMail;
use App\Models\MagicLinkToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequestMagicLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every test here except the throttling one exercises behaviour that
        // has nothing to do with rate limiting; keeping the limiter live would
        // couple unrelated tests to the per-IP budget.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_requesting_a_link_for_a_new_email_creates_the_user_and_sends_a_link(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/magic-link', [
            'email' => 'new@example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        Mail::assertQueued(MagicLinkMail::class, fn (MagicLinkMail $mail) => $mail->hasTo('new@example.com'));
    }

    public function test_requesting_a_link_for_an_existing_email_does_not_create_a_duplicate_user(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/auth/magic-link', ['email' => 'existing@example.com'])
            ->assertOk();

        $this->assertSame(1, User::where('email', 'existing@example.com')->count());
        Mail::assertQueued(MagicLinkMail::class);
    }

    public function test_the_response_never_leaks_account_existence_or_the_token(): void
    {
        Mail::fake();

        // The body must be identical whether or not the account already exists,
        // and must never contain the raw token or a user object.
        User::factory()->create(['email' => 'known@example.com']);

        $known = $this->postJson('/api/auth/magic-link', ['email' => 'known@example.com']);
        $unknown = $this->postJson('/api/auth/magic-link', ['email' => 'unknown@example.com']);

        $known->assertOk()->assertExactJson($unknown->json());
        $this->assertArrayNotHasKey('token', $known->json());
        $this->assertArrayNotHasKey('user', $known->json());
    }

    public function test_only_the_hashed_token_is_persisted_never_the_raw_value(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/magic-link', ['email' => 'hash@example.com'])->assertOk();

        // Pull the raw token out of the emailed link, then prove the DB stores
        // only its SHA-256 hash with a ~15 minute unexpired, unused lifetime.
        $rawToken = $this->rawTokenFromSentMail();

        $token = MagicLinkToken::sole();
        $this->assertSame(hash('sha256', $rawToken), $token->token_hash);
        $this->assertNull($token->used_at);
        $this->assertTrue($token->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
        $this->assertDatabaseMissing('magic_link_tokens', ['token_hash' => $rawToken]);
    }

    public function test_it_validates_the_email(): void
    {
        $this->postJson('/api/auth/magic-link', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('email');

        $this->postJson('/api/auth/magic-link', [])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    public function test_it_rate_limits_repeated_requests_for_the_same_email(): void
    {
        Mail::fake();

        // Limiter must be live for this one, so re-enable the middleware the
        // setUp stripped. The per-email budget is 5/hour.
        $this->withMiddleware(ThrottleRequests::class);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/magic-link', ['email' => 'flood@example.com'])->assertOk();
        }

        $this->postJson('/api/auth/magic-link', ['email' => 'flood@example.com'])
            ->assertStatus(429);
    }

    private function rawTokenFromSentMail(): string
    {
        $raw = null;

        Mail::assertQueued(MagicLinkMail::class, function (MagicLinkMail $mail) use (&$raw) {
            parse_str(parse_url($mail->url, PHP_URL_QUERY) ?: '', $query);
            $raw = $query['token'] ?? null;

            return true;
        });

        $this->assertNotNull($raw, 'No magic-link email was captured.');

        return $raw;
    }
}
