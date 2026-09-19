<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataRequest;
use App\Services\DataDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Self-serve data deletion (M7.3). Creates a deletion request and processes
 * it immediately (synchronous on shared hosting).
 */
class DataDeletionController extends Controller
{
    public function __construct(private DataDeletionService $deletionService)
    {
    }

    /**
     * Request account deletion.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $dataRequest = DataRequest::query()->create([
            'user_id' => $user->id,
            'type' => DataRequest::TYPE_DELETION,
            'status' => DataRequest::STATUS_PROCESSING,
            'requested_at' => now(),
        ]);

        $this->deletionService->delete($user, $dataRequest);

        return response()->json([
            'data' => [
                'request_id' => $dataRequest->id,
                'status' => DataRequest::STATUS_COMPLETED,
            ],
        ], 201);
    }
}
