<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\VerificationQuestionnaire;
use Illuminate\Http\JsonResponse;

/**
 * The visit questionnaire the platform provides (M25.1). Public so the app can
 * render it for volunteers; the volunteer is the verifier, not the platform.
 */
class VerificationQuestionnaireController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => VerificationQuestionnaire::all(),
        ]);
    }
}
