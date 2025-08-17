<?php

namespace mrmajestic\Seat\MoonExtractions\Jobs;

use mrmajestic\Seat\MoonExtractions\Models\MoonExtraction;
use Seat\Eveapi\Jobs\AbstractAuthCorporationJob;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Universe\UniverseSystem;
use Seat\Eveapi\Models\Universe\UniverseRegion;

class SyncCorporationMoonExtractions extends AbstractAuthCorporationJob
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/corporation/{corporation_id}/mining/extractions/';

    /**
     * @var string
     */
    protected $version = 'v1';

    /**
     * @var string
     */
    protected $scope = 'esi-industry.read_corporation_mining.v1';

    /**
     * @var array
     */
    protected $tags = ['corporation', 'mining', 'extractions'];

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $extractions = $this->retrieve([
            'corporation_id' => $this->getCorporationId(),
        ]);

        if ($extractions->isCachedLoad() &&
            MoonExtraction::where('corporation_id', $this->getCorporationId())->count() > 0) {
            return;
        }

        $corporation = CorporationInfo::find($this->getCorporationId());

        collect($extractions)->each(function ($extraction) use ($corporation) {
            // Get structure details
            $structure = $this->resolveStructureDetails($extraction->structure_id);

            // Get system and region information
            $system = UniverseSystem::find($extraction->moon->system_id) ?? null;
            $region = $system ? UniverseRegion::find($system->region_id) : null;

            // Get moon materials and calculate value
            $materials = $this->resolveMoonMaterials($extraction);
            $value = $this->calculateMoonValue($materials);

            // Save or update the extraction record
            MoonExtraction::updateOrCreate(
                [
                    'structure_id' => $extraction->structure_id,
                    'extraction_start_time' => $extraction->extraction_start_time,
                ],
                [
                    'structure_name' => $structure ? $structure->name : 'Unknown Structure',
                    'corporation_id' => $corporation->corporation_id,
                    'corporation_name' => $corporation->name,
                    'system_id' => $extraction->moon->system_id,
                    'system_name' => $system ? $system->name : 'Unknown System',
                    'region_id' => $system ? $system->region_id : null,
                    'region_name' => $region ? $region->name : 'Unknown Region',
                    'chunk_arrival_time' => $extraction->chunk_arrival_time,
                    'natural_decay_time' => $extraction->natural_decay_time,
                    'status' => $this->determineExtractionStatus($extraction),
                    'moon_materials' => json_encode($materials),
                    'moon_value' => $value,
                ]
            );
        });
    }

    // Helper methods (implement these based on your ESI integration)
    private function resolveStructureDetails($structureId) { /* Implementation */ }
    private function resolveMoonMaterials($extraction) { /* Implementation */ }
    private function calculateMoonValue($materials) { /* Implementation */ }

    private function determineExtractionStatus($extraction)
    {
        $now = now();

        if ($now < $extraction->extraction_start_time) {
            return 'scheduled';
        } elseif ($now >= $extraction->extraction_start_time && $now < $extraction->chunk_arrival_time) {
            return 'in_progress';
        } elseif ($now >= $extraction->chunk_arrival_time && $now < $extraction->natural_decay_time) {
            return 'ready';
        } else {
            return 'expired';
        }
    }
}
