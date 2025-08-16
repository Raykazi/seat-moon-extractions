<?php

namespace mrmajestic\Seat\MoonExtractions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Universe\UniverseSystem;
use Seat\Eveapi\Models\Universe\UniverseRegion;

use OpenApi\Annotations as OA;

/**
 * MoonExtraction Eloquent model.
 */
 
  @OA\Schema(
    schema="MoonExtraction",
    title="Moon Extraction",
    description="Moon extraction timer for a refinery owned by a corporation.",
    required={"structure_id","moon_id","extraction_start_time","chunk_arrival_time","natural_decay_time"},
 
    @OA\Property(
      property="id", type="integer", format="int64", readOnly=true,
      description="Internal DB id", example=42
    ),
    @OA\Property(
      property="corporation_id", type="integer", format="int32",
      description="Owning corporation ID", example=99001234
    ),
    @OA\Property(
      property="structure_id", type="integer", format="int64",
      description="Refinery structure ID", example=1023456789012
    ),
    @OA\Property(
      property="moon_id", type="integer", format="int32",
      description="EVE universe moon ID", example=40161465
    ),
    @OA\Property(
      property="extraction_start_time", type="string", format="date-time",
      description="When the laser was started", example="2025-08-16T12:34:56Z"
    ),
    @OA\Property(
      property="chunk_arrival_time", type="string", format="date-time",
      description="When the chunk arrives/ready to fracture", example="2025-08-20T12:34:56Z"
    ),
    @OA\Property(
      property="natural_decay_time", type="string", format="date-time",
      description="When the chunk naturally decays if not fractured", example="2025-08-22T12:34:56Z"
    ),
    @OA\Property(
      property="phase", type="string",
      description="Optional derived phase based on current time vs timers",
      enum={"scheduled","ready","decayed"}, example="scheduled", nullable=true
    ),
    @OA\Property(
      property="notes", type="string", nullable=true,
      description="Optional internal note"
    ),
    @OA\Property(
      property="created_at", type="string", format="date-time", readOnly=true
    ),
    @OA\Property(
      property="updated_at", type="string", format="date-time", readOnly=true
    )
  )
 
  @OA\Schema(
    schema="MoonExtractionCollection",
    type="array",
    @OA\Items(ref="#/components/schemas/MoonExtraction")
  )
 
class MoonExtraction extends Model
{
    protected $fillable = [
        'structure_id',
        'structure_name',
        'corporation_id',
        'corporation_name',
        'system_id',
        'system_name',
        'region_id',
        'region_name',
        'chunk_arrival_time',
        'extraction_start_time',
        'natural_decay_time',
        'moon_materials',
        'moon_value',
        'status',
    ];

    protected $casts = [
        'chunk_arrival_time' => 'datetime',
        'extraction_start_time' => 'datetime',
        'natural_decay_time' => 'datetime',
        'moon_materials' => 'array',
        'moon_value' => 'decimal:2',
    ];

    protected $dates = [
        'chunk_arrival_time',
        'extraction_start_time',
        'natural_decay_time',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the corporation that owns this extraction.
     */
    public function corporation(): BelongsTo
    {
        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }

    /**
     * Get the system where this extraction is taking place.
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(UniverseSystem::class, 'system_id', 'system_id');
    }

    /**
     * Get the region where this extraction is taking place.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(UniverseRegion::class, 'region_id', 'region_id');
    }

    /**
     * Scope to get active extractions (not completed or cancelled).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'active']);
    }

    /**
     * Scope to get extractions for a specific corporation.
     */
    public function scopeForCorporation($query, $corporationId)
    {
        return $query->where('corporation_id', $corporationId);
    }

    /**
     * Scope to get extractions in a specific system.
     */
    public function scopeInSystem($query, $systemId)
    {
        return $query->where('system_id', $systemId);
    }

    /**
     * Scope to get extractions happening within a time range.
     */
    public function scopeWithinTimeRange($query, $startTime, $endTime)
    {
        return $query->whereBetween('chunk_arrival_time', [$startTime, $endTime]);
    }

    /**
     * Get the estimated value of this extraction based on moon materials.
     */
    public function getEstimatedValueAttribute()
    {
        if (!$this->moon_materials || !is_array($this->moon_materials)) {
            return 0;
        }

        // This would need to be implemented based on current market prices
        // For now, return the stored moon_value or calculate based on materials
        return $this->moon_value ?: 0;
    }

    /**
     * Check if the extraction is currently active (chunk has arrived).
     */
    public function getIsActiveAttribute()
    {
        return now()->greaterThan($this->chunk_arrival_time) && 
               now()->lessThan($this->natural_decay_time);
    }

    /**
     * Get time remaining until chunk arrival.
     */
    public function getTimeToArrivalAttribute()
    {
        if (now()->greaterThan($this->chunk_arrival_time)) {
            return null;
        }

        return $this->chunk_arrival_time->diffInSeconds(now());
    }
}
