<?php

namespace App\Contracts;

use App\Models\MagicLinkToken;
use Illuminate\Support\Carbon;

interface MagicLinkTokenRepositoryContract
{
    /**
     * Persist a new magic-link token for a user.
     */
    public function create(int $userId, string $tokenHash, Carbon $expiresAt): MagicLinkToken;

    /**
     * Find a token by its stored hash, or null if none exists.
     */
    public function findByHash(string $tokenHash): ?MagicLinkToken;

    /**
     * Atomically mark a token used. Returns true only if this call was the one
     * that flipped an unused token to used — the basis of single-use redemption.
     */
    public function markUsed(int $id): bool;
}
