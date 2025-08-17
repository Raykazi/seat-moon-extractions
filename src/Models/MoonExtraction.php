<?php

namespace mrmajestic\Seat\MoonExtractions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoonExtraction extends Model
{
    use HasFactory;

    protected $table = 'moon_extractions';

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
        'status',
        'moon_materials',
        'moon_value',
    ];

    /**
     * Get the corporation that owns the extraction.
     */
    public function corporation()
    {
        return $this->belongsTo('Seat\Eveapi\Models\Corporation\CorporationInfo', 'corporation_id');
    }

    /**
     * Get the system that the extraction is located in.
     */
    public function system()
    {
        return $this->belongsTo('Seat\Eveapi\Models\Universe\UniverseSystem', 'system_id');
    }

    /**
     * Get the region that the extraction is located in.
     */
    public function region()
    {
        return $this->belongsTo('Seat\Eveapi\Models\Universe\UniverseRegion', 'region_id');
    }
}