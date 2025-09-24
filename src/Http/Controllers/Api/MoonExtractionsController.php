<?php

namespace raykazi\Seat\MoonExtractions\Http\Controllers\Api;

use Seat\Api\Http\Controllers\Api\v2\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Seat\Eveapi\Models\Industry\CorporationIndustryMiningExtraction;
use raykazi\Seat\MoonExtractions\Http\Resources\MoonExtractionResource;
use raykazi\Seat\MoonExtractions\Http\Resources\MoonExtractionCollection;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="MoonExtractionStatistics",
 *     type="object",
 *     title="Moon Extraction Statistics",
 *     description="Statistical information about moon extractions",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="total_extractions", type="integer", description="Total number of extractions"),
 *         @OA\Property(property="active_extractions", type="integer", description="Number of active extractions"),
 *         @OA\Property(property="completed_extractions", type="integer", description="Number of completed extractions"),
 *         @OA\Property(property="upcoming_24h", type="integer", description="Number of extractions in next 24 hours"),
 *         @OA\Property(property="total_estimated_value", type="number", description="Total estimated value of extractions"),
 *         @OA\Property(property="corporation_id", type="integer", description="Corporation ID (if filtered)", nullable=true)
 *     )
 * )
 */
class MoonExtractionsController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/v2/moon-extractions",
     *     summary="Get all moon extractions",
     *     description="Retrieve a list of moon extractions with optional filtering",
     *     operationId="getMoonExtractions",
     *     tags={"Moon Extractions"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Parameter(
     *         name="corporation_id",
     *         in="query",
     *         description="Filter by corporation ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="system_id",
     *         in="query",
     *         description="Filter by system ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="region_id",
     *         in="query",
     *         description="Filter by region ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "completed"})
     *     ),
     *     @OA\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="Start time filter (ISO 8601 format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="End time filter (ISO 8601 format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (max 1000)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=1000, default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/MoonExtractionCollection")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
        $perPage = min($request->get('per_page', 50), 1000);
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * @OA\Get(
     *     path="/api/v2/moon-extractions/{structureId}",
     *     summary="Get specific moon extraction by structure ID",
     *     description="Retrieve the latest moon extraction for a specific structure",
     *     operationId="getMoonExtractionByStructure",
     *     tags={"Moon Extractions"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Parameter(
     *         name="structureId",
     *         in="path",
     *         description="Structure ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/MoonExtractionResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Moon extraction not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show(Request $request, int $structureId): JsonResponse
    {
        $extraction = CorporationIndustryMiningExtraction::where('structure_id', $structureId)
            ->latest('chunk_arrival_time')
            ->firstOrFail();

        return response()->json(new MoonExtractionResource($extraction));
    }

    /**
     * @OA\Get(
     *     path="/api/v2/moon-extractions/corporation/{corporationId}",
     *     summary="Get moon extractions by corporation",
     *     description="Retrieve moon extractions for a specific corporation",
     *     operationId="getCorporationMoonExtractions",
     *     tags={"Moon Extractions"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Parameter(
     *         name="corporationId",
     *         in="path",
     *         description="Corporation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "completed"})
     *     ),
     *     @OA\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="Start time filter (ISO 8601 format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="End time filter (ISO 8601 format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (max 1000)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=1000, default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/MoonExtractionCollection")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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

        $perPage = min($request->get('per_page', 50), 1000);
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * @OA\Get(
     *     path="/api/v2/moon-extractions/system/{systemId}",
     *     summary="Get moon extractions by system",
     *     description="Retrieve moon extractions for a specific solar system",
     *     operationId="getSystemMoonExtractions",
     *     tags={"Moon Extractions"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         description="Solar System ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "completed"})
     *     ),
     *     @OA\Parameter(
     *         name="corporation_id",
     *         in="query",
     *         description="Filter by corporation ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (max 1000)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=1000, default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/MoonExtractionCollection")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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

        $perPage = min($request->get('per_page', 50), 1000);
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * @OA\Get(
     *     path="/api/v2/moon-extractions/upcoming",
     *     summary="Get upcoming moon extractions",
     *     description="Retrieve moon extractions scheduled for the near future",
     *     operationId="getUpcomingMoonExtractions",
     *     tags={"Moon Extractions"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Parameter(
     *         name="hours",
     *         in="query",
     *         description="Hours ahead to look for extractions (default: 24)",
     *         required=false,
     *         @OA\Schema(type="integer", default=24)
     *     ),
     *     @OA\Parameter(
     *         name="corporation_id",
     *         in="query",
     *         description="Filter by corporation ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="system_id",
     *         in="query",
     *         description="Filter by system ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (max 1000)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=1000, default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/MoonExtractionCollection")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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

        $perPage = min($request->get('per_page', 50), 1000);
        $extractions = $query->paginate($perPage);

        return response()->json(new MoonExtractionCollection($extractions));
    }

    /**
     * @OA\Get(
     *     path="/api/v2/moon-extractions/statistics",
     *     summary="Get extraction statistics",
     *     description="Retrieve statistical information about moon extractions",
     *     operationId="getMoonExtractionStatistics",
     *     tags={"Moon Extractions"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Parameter(
     *         name="corporation_id",
     *         in="query",
     *         description="Filter statistics by corporation ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/MoonExtractionStatistics")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
