<?php

namespace App\Services;

use App\Contracts\MagicLinkTokenRepositoryContract;
use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MagicLinkService
{
    public function __construct(
        private MagicLinkTokenRepositoryContract $tokens,
    ) {}

    /**
     * Issue a magic-link login token for an email and send the link.
     *
     * Magic-link is both signup and login, so an unknown email is a new user.
     * The raw token is only ever emailed; we persist just its SHA-256 hash.
     */
    public function requestLink(string $email): void
    {
        $user = User::firstOrCreate(['email' => $email]);

        $rawToken = Str::random(64);
        $ttlMinutes = (int) config('auth.magic_link.ttl_minutes');

        $this->tokens->create(
            $user->id,
            hash('sha256', $rawToken),
            now()->addMinutes($ttlMinutes),
        );

        $url = rtrim((string) config('app.frontend_url'), '/').'/auth/verify?token='.$rawToken;

        Mail::to($user->email)->send(new MagicLinkMail($url, $ttlMinutes));
    }

    /**
     * Redeem a raw magic-link token, returning the authenticated user and a
     * plaintext Sanctum bearer token — or null if the token is invalid,
     * expired, or already used.
     *
     * @return array{user: User, token: string}|null
     */
    public function redeem(string $rawToken): ?array
    {
        $token = $this->tokens->findByHash(hash('sha256', $rawToken));

        if ($token === null || $token->expires_at->isPast()) {
            return null;
        }

        // Atomic single-use claim: only the call that flips used_at wins,
        // so a double-clicked link can never issue two sessions.
        if (! $this->tokens->markUsed($token->id)) {
            return null;
        }

        $user = $token->user;

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return [
            'user' => $user,
            'token' => $user->createToken('spa')->plainTextToken,
        ];
    }
}
