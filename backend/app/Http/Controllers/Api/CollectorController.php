<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The collector's sub-division assignment (M28.1). Collectors only see their
 * own sub-division; they are not part of the driver flow.
 */
class CollectorController extends Controller
{
    public function assignment(Request $request): JsonResponse
    {
        $assignment = $request->user()->collectorAssignment?->load('locality.district');

        if ($assignment === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'is_active' => $assignment->is_active,
                'locality_id' => $assignment->locality_id,
                'locality' => $assignment->locality?->name,
                'district' => $assignment->locality?->district?->name,
                'assigned_at' => $assignment->assigned_at?->toIso8601String(),
            ],
        ]);
    }
}
