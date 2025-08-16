<?php

namespace mrmajestic\Seat\MoonExtractions\Http\Controllers\Api\V2;

// namespace Raykazi\Seat\PI\Http\Controllers\Api\V2;

use Seat\Api\Http\Controllers\Api\v2\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use mrmajestic\Seat\MoonExtractions\Models\MoonExtraction;
use mrmajestic\Seat\MoonExtractions\Http\Resources\MoonExtractionResource;
use mrmajestic\Seat\MoonExtractions\Http\Resources\MoonExtractionCollection;

class MoonExtractionsController extends Controller
{
    /**
     * Get all moon extractions with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MoonExtraction::query();

        // Apply filters
        if ($request->has('corporation_id')) {
            $query->forCorporation($request->corporation_id);
        }

        if ($request->has('system_id')) {
            $query->inSystem($request->system_id);
        }

        if ($request->has('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to active extractions only
            $query->active();
        }

        if ($request->has('start_time') && $request->has('end_time')) {
            $query->withinTimeRange($request->start_time, $request->end_time);
        }

        // Order by chunk arrival time
        $query->orderBy('chunk_arrival_time', 'asc');

        // Apply pagination
        $perPage = min($request->get('per_page', 50), config('moon-extractions.max_results', 1000));
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * Get a specific moon extraction by structure ID.
     */
    public function show(Request $request, int $structureId): JsonResponse
    {
        $extraction = MoonExtraction::where('structure_id', $structureId)->firstOrFail();
        
        return response()->json(new MoonExtractionResource($extraction));
    }

    /**
     * Get moon extractions for a specific corporation.
     */
    public function byCorporation(Request $request, int $corporationId): JsonResponse
    {
        $query = MoonExtraction::forCorporation($corporationId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active();
        }

        if ($request->has('start_time') && $request->has('end_time')) {
            $query->withinTimeRange($request->start_time, $request->end_time);
        }

        $query->orderBy('chunk_arrival_time', 'asc');

        $perPage = min($request->get('per_page', 50), config('moon-extractions.max_results', 1000));
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * Get moon extractions in a specific system.
     */
    public function bySystem(Request $request, int $systemId): JsonResponse
    {
        $query = MoonExtraction::inSystem($systemId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active();
        }

        if ($request->has('corporation_id')) {
            $query->forCorporation($request->corporation_id);
        }

        $query->orderBy('chunk_arrival_time', 'asc');

        $perPage = min($request->get('per_page', 50), config('moon-extractions.max_results', 1000));
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * Get upcoming extractions (next 24 hours by default).
     */
    public function upcoming(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);
        $startTime = now();
        $endTime = now()->addHours($hours);

        $query = MoonExtraction::withinTimeRange($startTime, $endTime)
            ->active()
            ->orderBy('chunk_arrival_time', 'asc');

        if ($request->has('corporation_id')) {
            $query->forCorporation($request->corporation_id);
        }

        if ($request->has('system_id')) {
            $query->inSystem($request->system_id);
        }

        $perPage = min($request->get('per_page', 50), config('moon-extractions.max_results', 1000));
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * Get extraction statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = MoonExtraction::query();

        if ($request->has('corporation_id')) {
            $query->forCorporation($request->corporation_id);
        }

        $stats = [
            'total_extractions' => $query->count(),
            'active_extractions' => $query->active()->count(),
            'completed_extractions' => $query->where('status', 'completed')->count(),
            'cancelled_extractions' => $query->where('status', 'cancelled')->count(),
            'upcoming_24h' => $query->active()
                ->withinTimeRange(now(), now()->addDay())
                ->count(),
            'total_estimated_value' => $query->active()->sum('moon_value'),
        ];

        if ($request->has('corporation_id')) {
            $stats['corporation_id'] = $request->corporation_id;
        }

        return response()->json(['data' => $stats]);
    }
}
