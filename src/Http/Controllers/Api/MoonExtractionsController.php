<?php

namespace raykazi\Seat\MoonExtractions\Http\Controllers\Api;

use Seat\Api\Http\Controllers\Api\v2\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Seat\Eveapi\Models\Industry\CorporationIndustryMiningExtraction;
use raykazi\Seat\MoonExtractions\Http\Resources\MoonExtractionResource;
use raykazi\Seat\MoonExtractions\Http\Resources\MoonExtractionCollection;
use Carbon\Carbon;

class MoonExtractionsController extends ApiController
{
    /**
     * Get all moon extractions with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CorporationIndustryMiningExtraction::query();

        // Apply filters
        if ($request->has('corporation_id')) {
            $query->where('corporation_id', $request->corporation_id);
        }

        if ($request->has('system_id')) {
            $query->whereHas('structure', function ($q) use ($request) {
                $q->where('system_id', $request->system_id);
            });
        }

        if ($request->has('region_id')) {
            $query->whereHas('structure.system', function ($q) use ($request) {
                $q->where('region_id', $request->region_id);
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('chunk_arrival_time', '>=', Carbon::now());
            } elseif ($request->status === 'completed') {
                $query->where('chunk_arrival_time', '<', Carbon::now());
            }
        } else {
            // Default to active extractions only
            $query->where('chunk_arrival_time', '>=', Carbon::now());
        }

        if ($request->has('start_time') && $request->has('end_time')) {
            $query->whereBetween('chunk_arrival_time', [
                Carbon::parse($request->start_time),
                Carbon::parse($request->end_time)
            ]);
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
        $extraction = CorporationIndustryMiningExtraction::where('structure_id', $structureId)
            ->latest('chunk_arrival_time')
            ->firstOrFail();

        return response()->json(new MoonExtractionResource($extraction));
    }

    /**
     * Get moon extractions for a specific corporation.
     */
    public function byCorporation(Request $request, int $corporationId): JsonResponse
    {
        $query = CorporationIndustryMiningExtraction::where('corporation_id', $corporationId);

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('chunk_arrival_time', '>=', Carbon::now());
            } elseif ($request->status === 'completed') {
                $query->where('chunk_arrival_time', '<', Carbon::now());
            }
        } else {
            $query->where('chunk_arrival_time', '>=', Carbon::now());
        }

        if ($request->has('start_time') && $request->has('end_time')) {
            $query->whereBetween('chunk_arrival_time', [
                Carbon::parse($request->start_time),
                Carbon::parse($request->end_time)
            ]);
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
        $query = CorporationIndustryMiningExtraction::whereHas('structure', function($q) use ($systemId) {
            $q->where('system_id', $systemId);
        });

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('chunk_arrival_time', '>=', Carbon::now());
            } elseif ($request->status === 'completed') {
                $query->where('chunk_arrival_time', '<', Carbon::now());
            }
        } else {
            $query->where('chunk_arrival_time', '>=', Carbon::now());
        }

        if ($request->has('corporation_id')) {
            $query->where('corporation_id', $request->corporation_id);
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

        $query = CorporationIndustryMiningExtraction::whereBetween('chunk_arrival_time', [$startTime, $endTime])
            ->orderBy('chunk_arrival_time', 'asc');

        if ($request->has('corporation_id')) {
            $query->where('corporation_id', $request->corporation_id);
        }

        if ($request->has('system_id')) {
            $query->whereHas('structure', function($q) use ($request) {
                $q->where('system_id', $request->system_id);
            });
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
        $query = CorporationIndustryMiningExtraction::query();

        if ($request->has('corporation_id')) {
            $query->where('corporation_id', $request->corporation_id);
        }

        $now = Carbon::now();

        $stats = [
            'total_extractions' => $query->count(),
            'active_extractions' => $query->where('chunk_arrival_time', '>=', $now)->count(),
            'completed_extractions' => $query->where('chunk_arrival_time', '<', $now)->count(),
//            'cancelled_extractions' => $query->whereNotNull('canceled_by')->count(),
            'upcoming_24h' => $query->whereBetween('chunk_arrival_time', [
                $now,
                $now->copy()->addDay()
            ])->count(),
            'total_estimated_value' => 0, // This model doesn't have a moon_value field
        ];

        if ($request->has('corporation_id')) {
            $stats['corporation_id'] = $request->corporation_id;
        }

        return response()->json(['data' => $stats]);
    }
}