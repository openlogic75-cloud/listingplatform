<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\UploadValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Image upload for the app (M2.3). One shared validator for every upload
 * type: product images now, UPI QR (M6.2) and evidence (M5.2) later.
 */
class MediaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.UploadValidator::MAX_KILOBYTES],
            'directory' => ['nullable', 'string', 'in:products,avatars'],
        ]);

        $result = UploadValidator::validateAndStore(
            $request->file('file'),
            $request->input('directory', 'products'),
        );

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $result['path'],
            'mime_type' => $result['mime'],
            'size' => $result['size'],
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'id' => $media->id,
            'path' => $media->path,
            'mime' => $media->mime_type,
            'url' => \Storage::disk('public')->url($media->path),
        ], 201);
    }
}
