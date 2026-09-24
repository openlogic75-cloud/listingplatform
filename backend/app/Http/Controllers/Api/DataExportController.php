<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataRequest;
use App\Services\DataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Self-serve data export (M7.2). Creates a data request record and generates
 * a machine-readable export file. The user can download it once ready.
 */
class DataExportController extends Controller
{
    public function __construct(private DataExportService $exportService) {}

    /**
     * Request a data export.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $dataRequest = DataRequest::query()->create([
            'user_id' => $user->id,
            'type' => DataRequest::TYPE_EXPORT,
            'status' => DataRequest::STATUS_PROCESSING,
            'requested_at' => now(),
        ]);

        // Generate the export (synchronous on shared hosting).
        $path = $this->exportService->export($user);

        $dataRequest->update([
            'status' => DataRequest::STATUS_COMPLETED,
            'processed_at' => now(),
            'notes' => 'private:'.$path,
        ]);

        return response()->json([
            'data' => [
                'request_id' => $dataRequest->id,
                'status' => DataRequest::STATUS_COMPLETED,
                'download_url' => URL::temporarySignedRoute(
                    'api.exports.download',
                    now()->addMinutes(10),
                    ['dataRequest' => $dataRequest->id],
                ),
            ],
        ], 201);
    }
}
