<?php

namespace raykazi\Seat\MoonExtractions\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MoonExtractionCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => MoonExtractionResource::collection($this->collection),
            'links' => [
                'self' => url()->current(),
            ],
            'meta' => [
                'count' => $this->count(),
                'total' => $this->total(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }
}