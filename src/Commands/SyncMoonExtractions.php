<?php

namespace mrmajestic\Seat\MoonExtractions\Commands;

use Illuminate\Console\Command;
use mrmajestic\Seat\MoonExtractions\Jobs\SyncCorporationMoonExtractions;
use Seat\Console\Commands\ScheduleCommand;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\RefreshToken;

class SyncMoonExtractions extends Command
{
    protected $signature = 'moon:sync-extractions {--corporation_id= : The corporation ID to sync}';

    protected $description = 'Syncs moon extraction data from EVE ESI API';

    public function handle()
    {
        $corporationId = $this->option('corporation_id');

        if ($corporationId) {
            $corporations = CorporationInfo::where('corporation_id', $corporationId)->get();
        } else {
            // Get corporations that have characters with valid tokens and required scopes
            $corporations = CorporationInfo::whereHas('characters.refresh_token', function ($query) {
                $query->whereJsonContains('scopes', 'esi-industry.read_corporation_mining.v1');
            })->get();
        }

        $this->info('Syncing moon extraction data for ' . $corporations->count() . ' corporations.');

        foreach ($corporations as $corporation) {
            // Dispatch a job to sync the moon extraction data for this corporation
            $job = new SyncCorporationMoonExtractions($corporation->corporation_id);
            dispatch($job);

            $this->info('Jobs dispatched for corporation ' . $corporation->name . ' (' . $corporation->corporation_id . ')');
        }

        $this->info('Moon extraction sync jobs have been dispatched.');
    }


}