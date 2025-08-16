<?php

namespace YourNamespace\Seat\MoonExtractions\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\RefreshToken;
use YourNamespace\Seat\MoonExtractions\Models\MoonExtraction;
use YourNamespace\Seat\MoonExtractions\Jobs\Sync\SyncCorporationExtractions;

class SyncMoonExtractions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'moon-extractions:sync 
                            {--corporation-id= : Sync specific corporation ID}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     */
    protected $description = 'Sync moon extraction data from EVE API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting moon extractions sync...');

        $corporationId = $this->option('corporation-id');
        $force = $this->option('force');

        if ($corporationId) {
            $this->syncCorporation($corporationId, $force);
        } else {
            $this->syncAllCorporations($force);
        }

        $this->info('Moon extractions sync completed.');
        
        return self::SUCCESS;
    }

    /**
     * Sync a specific corporation's moon extractions.
     */
    private function syncCorporation(int $corporationId, bool $force = false): void
    {
        $cacheKey = "moon-extractions:sync:{$corporationId}";
        
        if (!$force && Cache::has($cacheKey)) {
            $this->warn("Corporation {$corporationId} was recently synced. Use --force to override.");
            return;
        }

        $corporation = CorporationInfo::find($corporationId);
        if (!$corporation) {
            $this->error("Corporation {$corporationId} not found.");
            return;
        }

        // Check if we have a valid refresh token for this corporation
        $token = RefreshToken::whereHas('character', function ($query) use ($corporationId) {
            $query->where('corporation_id', $corporationId);
        })->whereHas('scopes', function ($query) {
            $query->where('scope', 'esi-industry.read_corporation_mining.v1');
        })->first();

        if (!$token) {
            $this->warn("No valid token found for corporation {$corporationId} with required scopes.");
            return;
        }

        $this->info("Syncing moon extractions for corporation: {$corporation->name}");

        // Dispatch the sync job
        SyncCorporationExtractions::dispatch($corporationId, $token);

        // Cache the sync to prevent too frequent updates
        Cache::put($cacheKey, now(), config('moon-extractions.sync_interval', 900));

        $this->info("Sync job dispatched for corporation: {$corporation->name}");
    }

    /**
     * Sync all corporations with valid tokens.
     */
    private function syncAllCorporations(bool $force = false): void
    {
        // Get all corporations that have tokens with the required scope
        $corporationIds = RefreshToken::whereHas('scopes', function ($query) {
            $query->where('scope', 'esi-industry.read_corporation_mining.v1');
        })->with('character')
            ->get()
            ->pluck('character.corporation_id')
            ->unique()
            ->filter();

        if ($corporationIds->isEmpty()) {
            $this->warn('No corporations found with valid tokens for moon extractions.');
            return;
        }

        $this->info("Found {$corporationIds->count()} corporations to sync.");

        $bar = $this->output->createProgressBar($corporationIds->count());
        $bar->start();

        foreach ($corporationIds as $corporationId) {
            $this->syncCorporation($corporationId, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
