<?php

namespace mrmajestic\Seat\MoonExtractions\Commands;

use Illuminate\Console\Command;
use mrmajestic\Seat\MoonExtractions\Jobs\Sync\SyncCorporationExtractions;
use Seat\Eveapi\Models\Corporation\CorporationInfo;

class SyncAllCorporationExtractions extends Command
{
    protected $signature = 'moon-extractions:sync-all';
    protected $description = 'Sync moon extractions for all corporations';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {

        parent::__construct();

    }
    
    public function handle(): void
    {
        $corporations = CorporationInfo::all();

        foreach ($corporations as $corporation) {
            $this->info("Syncing extractions for corporation: {$corporation->name} (ID: {$corporation->corporation_id})");
            SyncCorporationExtractions::dispatch($corporation->corporation_id);
        }

        $this->info('All corporation extractions sync jobs have been dispatched.');
    }
}