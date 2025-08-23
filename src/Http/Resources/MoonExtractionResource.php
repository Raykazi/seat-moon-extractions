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
            'solar_system_name' => SolarSystem::find($corporationStructure->solar_system_id)->name ?? 'Unknown System',
            'moon_id' => $this->moon_id,
            'moon_name' => $moon->name ?? 'Unknown Moon',
            'extraction_start_time' => $this->extraction_start_time,
            'chunk_arrival_time' => $this->chunk_arrival_time,
            'natural_decay_time' => $this->natural_decay_time,
            'status' => $this->chunk_arrival_time >= now() ? 'active' : 'completed',
            'moon_rarity' =>  $this->getMoonRarity($this->moon_id),
            'content' =>  $this->getMoonContents($this->moon_id, $this->volume()),
        ];
    }
    public function getMoonContents($moon_id, $volume)
    {
        // Get the moon by ID
        $moon = Moon::find($moon_id);

        if (!$moon->moon_report) {
            return "No moon report available";
        }
        $composition = $moon->moon_report->content;

        $returnArray = [];
        if ($composition) {
            foreach ($composition as $material) {
                $returnArray[] = [
                    'typeName' => $material['typeName'],
                    'volume' => $material['volume'],
                    'rate' => $material['pivot']->rate,
                    'm3' => round($material['pivot']->rate * $volume, 2),
                    'rarity' => $this->getItemRarity($material['typeName']),
                ];
            }
            return $returnArray;
        }

        return "No composition data available";
    }

    /**
     * Get the rarity of the moon based on its composition.
     *
     * @param int $moon_id
     * @return string
     */
    public function getMoonRarity($moon_id)
    {
        // Get the moon by ID
        $moon = \Seat\Eveapi\Models\Sde\Moon::find($moon_id);

        if (!$moon->moon_report) {
            return "No moon report available";
        }

        // Assuming moon_report->content contains composition data
        $composition = $moon->moon_report->content;

        // Check for highest rarity materials present
        foreach ($composition as $material) {
            $material_name = $material['typeName'];
            return  $this->getItemRarity($material_name);
        }

        return "Rarity Not Found (Report To HNIC)"; // No recognized moon materials found
    }

    public function getItemRarity($itemName)
    {
        // Define item rarities
        $rarities = [
            'R64' => ['Promethium', 'Technetium', 'Dysprosium', 'Neodymium'],
            'R32' => ['Caesium', 'Hafnium', 'Mercury', 'Thulium'],
            'R16' => ['Cadmium', 'Cobalt', 'Scandium', 'Titanium', 'Tungsten', 'Vanadium'],
            'R8'  => ['Chromite', 'Euxenite', 'Scheelite', 'Otavite', 'Sperrylite'],
            'R4'  => ['Bitumens', 'Coesite', 'Sylvite', 'Zeolites'],
        ];

        foreach ($rarities as $rarity => $items) {
            if (in_array($itemName, $items)) {
                return $rarity;
            }
        }

        return "Unknown Rarity";
    }
}