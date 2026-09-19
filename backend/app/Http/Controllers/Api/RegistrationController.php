<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function __construct(private RegistrationService $registrations) {}

    /**
     * Registration is limited to the roles that need accounts. Buyers browse
     * as guests and never register (Q3 decided). Collectors are logistics
     * staff and self-register like drivers.
     */
    public function register(Request $request): JsonResponse
    {
        $user = $this->registrations->register($request->all());

        $token = $user->createToken('registration')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->profile(),
        ], 201);
    }
}
