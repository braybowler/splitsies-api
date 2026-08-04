<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        // The token row is gone, so it can no longer authenticate a fresh
        // request. (Asserting via a second in-process request would hit the
        // auth guard's per-process user cache, not real behaviour; the
        // selective-revocation test below proves revoked tokens are removed.)
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_it_only_revokes_the_token_that_made_the_request(): void
    {
        $user = User::factory()->create();
        $keep = $user->createToken('other-device')->plainTextToken;
        $revoke = $user->createToken('this-device')->plainTextToken;

        $this->withToken($revoke)->postJson('/api/auth/logout')->assertNoContent();

        // The user's other session stays valid.
        $this->assertSame(1, $user->tokens()->count());
        $this->withToken($keep)->getJson('/api/user')->assertOk();
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }
}
