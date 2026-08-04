<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_currency' => $this->base_currency,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            // The requesting user's role in this trip. `members` is eager-loaded
            // on both list and show, so this needs no extra query.
            'role' => $this->members->firstWhere('user_id', $request->user()->id)?->role,
            'members' => TripMemberResource::collection($this->whenLoaded('members')),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
        ];
    }
}
