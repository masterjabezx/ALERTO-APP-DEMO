<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\IncidentCategory;
use App\Models\Municipality;
use App\Models\Office;
use App\Models\ResponseTeam;
use App\Services\GeoService;
use App\Services\LiveOperationsMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLiveOperationsMapController extends Controller
{
    public function __construct(
        protected LiveOperationsMapService $mapService,
        protected GeoService $geoService
    ) {}

    /**
     * Display the full-screen Admin Live Operations Map.
     */
    public function index(): View
    {
        $categories = IncidentCategory::all();
        $barangays = Barangay::all();
        $offices = Office::where('is_active', true)->get();
        $teams = ResponseTeam::where('is_active', true)->get();
        $initialData = $this->mapService->getAdminMapData();

        return view('admin.map.index', [
            'categories' => $categories,
            'barangays' => $barangays,
            'offices' => $offices,
            'teams' => $teams,
            'initialData' => $initialData,
        ]);
    }

    /**
     * JSON API Endpoint returning live map telemetry, markers, routes, and boundaries.
     */
    public function data(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category_id',
            'barangay_id',
            'status',
            'priority',
            'office_id',
            'team_id',
            'personnel_id',
            'personnel_status',
            'search',
        ]);

        $bbox = null;
        if ($request->filled(['min_lat', 'max_lat', 'min_lng', 'max_lng'])) {
            $bbox = [
                'min_lat' => (float) $request->input('min_lat'),
                'max_lat' => (float) $request->input('max_lat'),
                'min_lng' => (float) $request->input('min_lng'),
                'max_lng' => (float) $request->input('max_lng'),
            ];
        }

        $data = $this->mapService->getAdminMapData($filters, $bbox);

        return response()->json($data);
    }

    /**
     * Display the verified GeoJSON boundary management console.
     */
    public function boundaries(): View
    {
        $municipalities = Municipality::all();
        $barangays = Barangay::with('boundary')->get();

        return view('admin.map.boundaries', [
            'municipalities' => $municipalities,
            'barangays' => $barangays,
        ]);
    }

    /**
     * Import official GeoJSON dataset for municipal or barangay boundaries.
     */
    public function importBoundaries(Request $request): RedirectResponse
    {
        $request->validate([
            'boundary_type' => ['required', 'in:municipality,barangay'],
            'target_id' => ['required', 'integer'],
            'source_information' => ['required', 'string', 'max:255'],
            'geojson_file' => ['nullable', 'file', 'mimes:json,geojson,txt', 'max:10240'],
            'geojson_content' => ['nullable', 'string'],
        ]);

        $geojson = '';
        if ($request->hasFile('geojson_file')) {
            $geojson = (string) file_get_contents($request->file('geojson_file')->getRealPath());
        } elseif ($request->filled('geojson_content')) {
            $geojson = (string) $request->input('geojson_content');
        } else {
            return back()->with('error', 'Please provide a valid GeoJSON file or JSON string.');
        }

        try {
            $type = $request->input('boundary_type');
            $targetId = (int) $request->input('target_id');
            $source = (string) $request->input('source_information');

            if ($type === 'municipality') {
                $this->geoService->importMunicipalityBoundary($targetId, $geojson, $source);
                $msg = 'Hinoba-an municipal boundary imported and verified successfully.';
            } else {
                $this->geoService->importBarangayBoundary($targetId, $geojson, $source);
                $msg = 'Barangay boundary imported and verified successfully.';
            }

            return redirect()->route('admin.map.boundaries')->with('success', $msg);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'GeoJSON Validation Error: '.$e->getMessage());
        }
    }
}
