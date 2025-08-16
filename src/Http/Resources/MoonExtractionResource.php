<?php

namespace MrMajestic\Seat\MoonExtractions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoonExtractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'structure_id' => $this->structure_id,
            'structure_name' => $this->structure_name,
            'corporation' => [
                'id' => $this->corporation_id,
                'name' => $this->corporation_name,
            ],
            'location' => [
                'system' => [
                    'id' => $this->system_id,
                    'name' => $this->system_name,
                ],
                'region' => [
                    'id' => $this->region_id,
                    'name' => $this->region_name,
                ],
            ],
            'extraction' => [
                'start_time' => $this->extraction_start_time?->toISOString(),
                'chunk_arrival_time' => $this->chunk_arrival_time?->toISOString(),
                'natural_decay_time' => $this->natural_decay_time?->toISOString(),
                'status' => $this->status,
                'is_active' => $this->is_active,
                'time_to_arrival_seconds' => $this->time_to_arrival,
            ],
            'moon_materials' => $this->moon_materials,
            'estimated_value' => [
                'amount' => $this->moon_value,
                'currency' => 'ISK',
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}
