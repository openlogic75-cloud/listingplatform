<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\JsonResponse;

/**
 * Public district/locality reference data. Used by the app for the rider
 * base-of-operation picker and guest errand location pickers. No PII.
 */
class LocationsController extends Controller
{
    public function index(): JsonResponse
    {
        $districts = District::query()
            ->where('is_active', true)
            ->with(['localities' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $districts->map(fn (District $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'localities' => $d->localities->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                ]),
            ]),
        ]);
    }
}