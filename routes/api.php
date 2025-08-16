<?php

use Illuminate\Support\Facades\Route;
use YourNamespace\Seat\MoonExtractions\Http\Controllers\Api\MoonExtractionsController;

/*
|--------------------------------------------------------------------------
| Moon Extractions API Routes
|--------------------------------------------------------------------------
|
| API routes for the SeAT Moon Extractions plugin. All routes are prefixed
| with 'api/v1/moon-extractions' and require authentication.
|
*/

Route::prefix('api/v1/moon-extractions')
    ->middleware(['auth:api', 'throttle:' . config('moon-extractions.api_rate_limit', 60) . ',1'])
    ->group(function () {
        
        // Get all extractions with optional filtering
        Route::get('/', [MoonExtractionsController::class, 'index'])
            ->name('api.moon-extractions.index');
        
        // Get extraction statistics
        Route::get('/statistics', [MoonExtractionsController::class, 'statistics'])
            ->name('api.moon-extractions.statistics');
        
        // Get upcoming extractions
        Route::get('/upcoming', [MoonExtractionsController::class, 'upcoming'])
            ->name('api.moon-extractions.upcoming');
        
        // Get extractions by corporation
        Route::get('/corporation/{corporationId}', [MoonExtractionsController::class, 'byCorporation'])
            ->name('api.moon-extractions.by-corporation')
            ->whereNumber('corporationId');
        
        // Get extractions by system
        Route::get('/system/{systemId}', [MoonExtractionsController::class, 'bySystem'])
            ->name('api.moon-extractions.by-system')
            ->whereNumber('systemId');
        
        // Get specific extraction by structure ID
        Route::get('/structure/{structureId}', [MoonExtractionsController::class, 'show'])
            ->name('api.moon-extractions.show')
            ->whereNumber('structureId');
    });
