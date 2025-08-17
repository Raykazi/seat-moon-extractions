<?php

namespace raykazi\Seat\MoonExtractions\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Corporation\CorporationStructure;
use Seat\Eveapi\Models\Sde\Moon;
use Seat\Eveapi\Models\Universe\UniverseStructure;
use Seat\Web\Models\UniverseMoonReport;

class MoonExtractionResource extends JsonResource
{
    public function toArray($request)
    {
        // Get related data
        $corporation = CorporationInfo::find($this->corporation_id);
        $moon = Moon::find($this->moon_id);
        $corporationStructure = UniverseStructure::with('type')->find($this->structure_id);
        return [
            'id' => $this->id,
            'corporation_id' => $this->corporation_id,
            'corporation_name' => $corporation->name ?? 'Unknown Corporation',
            'structure_id' => $this->structure_id,
            'name' => $corporationStructure->name ?? 'Unknown Structure',
            'solar_system_id' => $corporationStructure->solar_system_id,
            'solar_system_name' => \Seat\Eveapi\Models\Sde\SolarSystem::find($corporationStructure->solar_system_id)->name ?? 'Unknown System',
            'moon_id' => $this->moon_id,
            'moon_name' => $moon->name ?? 'Unknown Moon',
            'extraction_start_time' => $this->extraction_start_time,
            'chunk_arrival_time' => $this->chunk_arrival_time,
            'natural_decay_time' => $this->natural_decay_time,
            'status' => $this->chunk_arrival_time >= now() ? 'active' : 'completed',
//            'moon_info' => $moon->toArray(),
        ];
    }
}