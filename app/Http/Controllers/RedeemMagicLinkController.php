<?php

namespace App\Http\Controllers;

use App\Http\Requests\RedeemMagicLinkRequest;
use App\Http\Resources\UserResource;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;

class RedeemMagicLinkController extends Controller
{
    public function __construct(
        private MagicLinkService $magicLinks,
    ) {}

    /**
     * Redeem a magic-link token and issue a Sanctum bearer token.
     *
     * Invalid, expired, and already-used tokens all return the same 422 so a
     * caller can't distinguish the cases.
     */
    public function __invoke(RedeemMagicLinkRequest $request): JsonResponse
    {
        $result = $this->magicLinks->redeem($request->string('token')->toString());

        if ($result === null) {
            return response()->json([
                'message' => 'This login link is invalid or has expired.',
            ], 422);
        }

        return response()->json([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ]);
    }
}
