<?php

namespace App\Repositories;

use App\Contracts\MagicLinkTokenRepositoryContract;
use App\Models\MagicLinkToken;
use Illuminate\Support\Carbon;

class MagicLinkTokenRepository implements MagicLinkTokenRepositoryContract
{
    public function create(int $userId, string $tokenHash, Carbon $expiresAt): MagicLinkToken
    {
        return MagicLinkToken::create([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findByHash(string $tokenHash): ?MagicLinkToken
    {
        return MagicLinkToken::query()
            ->where('token_hash', $tokenHash)
            ->first();
    }

    public function markUsed(int $id): bool
    {
        return MagicLinkToken::query()
            ->whereKey($id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]) === 1;
    }
}
