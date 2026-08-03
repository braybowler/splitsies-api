<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestMagicLinkRequest;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;

class RequestMagicLinkController extends Controller
{
    public function __construct(
        private MagicLinkService $magicLinks,
    ) {}

    /**
     * Email a single-use magic login link to the given address.
     *
     * The response is intentionally uniform whether or not the account exists,
     * so it never leaks account existence or the token itself.
     */
    public function __invoke(RequestMagicLinkRequest $request): JsonResponse
    {
        $this->magicLinks->requestLink($request->string('email')->toString());

        return response()->json([
            'message' => "If that email can log in, we've sent a link.",
        ]);
    }
}
