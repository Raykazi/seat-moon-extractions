<?php

namespace YourNamespace\Seat\MoonExtractions\Jobs\Sync;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Seat\Eveapi\Jobs\AbstractAuthCorporationJob;
use Seat\Eveapi\Models\RefreshToken;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use YourNamespace\Seat\MoonExtractions\Models\MoonExtraction;

class SyncCorporationExtractions extends AbstractAuthCorporationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting moon extractions sync for corporation', [
            'corporation_id' => $this->getCorporationId(),
        ]);

        try {
            $this->syncExtractions();
        } catch (\Exception $exception) {
            Log::error('Failed to sync moon extractions', [
                'corporation_id' => $this->getCorporationId(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Sync moon extractions from EVE API.
     */
    private function syncExtractions(): void
    {
        $corporationId = $this->getCorporationId();

        // Get corporation info for name
        $corporation = CorporationInfo::find($corporationId);
        if (!$corporation) {
            Log::warning('Corporation not found', ['corporation_id' => $corporationId]);
            return;
        }

        // Get moon extractions from EVE API
        $extractions = $this->retrieveFromESI('get', '/corporations/{corporation_id}/mining/extractions/', [
            'corporation_id' => $corporationId,
        ]);

        if (!$extractions) {
            Log::warning('No extractions data received from ESI', ['corporation_id' => $corporationId]);
            return;
        }

        Log::info('Retrieved extractions from ESI', [
            'corporation_id' => $corporationId,
            'count' => count($extractions),
        ]);

        foreach ($extractions as $extractionData) {
            $this->updateOrCreateExtraction($extractionData, $corporation);
        }

        // Mark old extractions as completed if they're past natural decay time
        $this->markCompletedExtractions($corporationId);
    }

    /**
     * Update or create a moon extraction record.
     */
    private function updateOrCreateExtraction(array $extractionData, CorporationInfo $corporation): void
    {
        try {
            $structureId = $extractionData['structure_id'];

            // Get structure information (you might want to cache this)
            $structureInfo = $this->getStructureInfo($structureId);
            $systemInfo = $this->getSystemInfo($structureInfo['solar_system_id'] ?? null);

            $extractionRecord = [
                'structure_id' => $structureId,
                'structure_name' => $structureInfo['name'] ?? "Structure {$structureId}",
                'corporation_id' => $corporation->corporation_id,
                'corporation_name' => $corporation->name,
                'system_id' => $systemInfo['system_id'] ?? null,
                'system_name' => $systemInfo['name'] ?? 'Unknown System',
                'region_id' => $systemInfo['region_id'] ?? null,
                'region_name' => $systemInfo['region_name'] ?? 'Unknown Region',
                'chunk_arrival_time' => $extractionData['chunk_arrival_time'],
                'extraction_start_time' => $extractionData['extraction_start_time'],
                'natural_decay_time' => $extractionData['natural_decay_time'],
                'status' => $this->determineStatus($extractionData),
            ];

            // Get moon materials if available
            if (isset($extractionData['moon_id'])) {
                $moonMaterials = $this->getMoonMaterials($extractionData['moon_id']);
                $extractionRecord['moon_materials'] = $moonMaterials;
                $extractionRecord['moon_value'] = $this->calculateMoonValue($moonMaterials);
            }

            MoonExtraction::updateOrCreate(
                ['structure_id' => $structureId],
                $extractionRecord
            );

            Log::debug('Updated moon extraction', [
                'structure_id' => $structureId,
                'corporation_id' => $corporation->corporation_id,
            ]);

        } catch (\Exception $exception) {
            Log::error('Failed to update moon extraction', [
                'structure_id' => $extractionData['structure_id'] ?? 'unknown',
                'corporation_id' => $corporation->corporation_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Get structure information from ESI.
     */
    private function getStructureInfo(int $structureId): array
    {
        try {
            return $this->retrieveFromESI('get', '/universe/structures/{structure_id}/', [
                'structure_id' => $structureId,
            ]) ?: [];
        } catch (\Exception $exception) {
            Log::warning('Failed to get structure info', [
                'structure_id' => $structureId,
                'error' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get system information.
     */
    private function getSystemInfo(?int $systemId): array
    {
        if (!$systemId) {
            return [];
        }

        try {
            // This should use SeAT's universe data models
            $system = \Seat\Eveapi\Models\Universe\UniverseSystem::find($systemId);
            if ($system) {
                return [
                    'system_id' => $system->system_id,
                    'name' => $system->name,
                    'region_id' => $system->region_id,
                    'region_name' => $system->region->name ?? 'Unknown Region',
                ];
            }
        } catch (\Exception $exception) {
            Log::warning('Failed to get system info', [
                'system_id' => $systemId,
                'error' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Get moon materials for a moon.
     */
    private function getMoonMaterials(int $moonId): ?array
    {
        try {
            // This would need to be implemented based on available SeAT models
            // or direct ESI calls to get moon composition
            return null;
        } catch (\Exception $exception) {
            Log::warning('Failed to get moon materials', [
                'moon_id' => $moonId,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate estimated moon value.
     */
    private function calculateMoonValue(?array $moonMaterials): ?float
    {
        if (!$moonMaterials) {
            return null;
        }

        // This would need to be implemented based on current market prices
        // You might want to use SeAT's market data or integrate with a pricing API
        return null;
    }

    /**
     * Determine the status of an extraction.
     */
    private function determineStatus(array $extractionData): string
    {
        $now = now();
        $chunkArrival = new \DateTime($extractionData['chunk_arrival_time']);
        $naturalDecay = new \DateTime($extractionData['natural_decay_time']);

        if ($now->lt($chunkArrival)) {
            return 'scheduled';
        }

        if ($now->gte($chunkArrival) && $now->lt($naturalDecay)) {
            return 'active';
        }

        return 'completed';
    }

    /**
     * Mark old extractions as completed.
     */
    private function markCompletedExtractions(int $corporationId): void
    {
        $updated = MoonExtraction::where('corporation_id', $corporationId)
            ->where('natural_decay_time', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->update(['status' => 'completed']);

        if ($updated > 0) {
            Log::info("Marked {$updated} extractions as completed for corporation {$corporationId}");
        }
    }
}
