<?php

declare(strict_types=1);

namespace App\Http\Controllers\Map;

use App\Http\Controllers\Controller;
use App\Services\GeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteNavigationController extends Controller
{
    public function __construct(
        protected GeoService $geoService
    ) {}

    /**
     * Calculate route navigation metrics and waypoints between two GPS coordinates.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_lat' => ['required', 'numeric', 'between:-90,90'],
            'origin_lng' => ['required', 'numeric', 'between:-180,180'],
            'dest_lat' => ['required', 'numeric', 'between:-90,90'],
            'dest_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $route = $this->geoService->calculateRoute(
            (float) $validated['origin_lat'],
            (float) $validated['origin_lng'],
            (float) $validated['dest_lat'],
            (float) $validated['dest_lng']
        );

        return response()->json([
            'status' => 'success',
            'data' => $route,
        ]);
    }
}
