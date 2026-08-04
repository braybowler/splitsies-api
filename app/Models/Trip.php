<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TripRole;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'base_currency', 'owner_id', 'starts_on', 'ends_on'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<TripMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(TripMember::class);
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function roleFor(User $user): ?TripRole
    {
        $role = $this->members()->where('user_id', $user->id)->value('role');

        return $role === null ? null : TripRole::from($role);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_currency' => Currency::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
