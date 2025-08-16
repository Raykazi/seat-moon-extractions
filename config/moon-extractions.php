<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moon Extractions Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the SeAT Moon Extractions plugin
    |
    */

    'cache_duration' => env('MOON_EXTRACTIONS_CACHE_DURATION', 3600), // 1 hour in seconds
    
    'api_rate_limit' => env('MOON_EXTRACTIONS_API_RATE_LIMIT', 60), // requests per minute
    
    'sync_interval' => env('MOON_EXTRACTIONS_SYNC_INTERVAL', 900), // 15 minutes in seconds
    
    'include_completed' => env('MOON_EXTRACTIONS_INCLUDE_COMPLETED', false),
    
    'max_results' => env('MOON_EXTRACTIONS_MAX_RESULTS', 1000),
];
