<?php


/*
|--------------------------------------------------------------------------
| Moon Extractions API Routes
|--------------------------------------------------------------------------
|
| API routes for the SeAT Moon Extractions plugin. All routes are prefixed
| with 'api/v1/moon-extractions' and require authentication.
|
*/
Route::group([
    'namespace' => 'mrmajestic\Seat\MoonExtractions\Http\Controllers',
], function () {
    Route::group([
        'prefix'     => 'moon-extractions',
        'middleware' => ['web', 'auth', 'locale'],
    ], function () {
    });
    Route::group([
        'namespace'  => 'Api',
        'middleware' => ['api.request', 'api.auth', 'throttle:' . config('moon-extractions.api_rate_limit', 60) . ',1'],
        'prefix'     => 'api',
    ], function () {
        Route::group([
            'namespace' => 'V2',
            'prefix'    => 'v2',
        ], function () {
            Route::group([
                'prefix' => 'moon-extractions',
            ], function () {
				// Get all extractions with optional filtering
				Route::get('/')->uses('MoonExtractionsController@index');
					// ->name('api.moon-extractions.index');
				
				// Get extraction statistics
				Route::get('/statistics')->uses('MoonExtractionsController@statistics');
					// ->name('api.moon-extractions.statistics');
				
				// // Get upcoming extractions
				// Route::get('/upcoming', [MoonExtractionsController::class, 'upcoming'])
				// 	->name('api.moon-extractions.upcoming');
				
				// // Get extractions by corporation
				// Route::get('/corporation/{corporationId}', [MoonExtractionsController::class, 'byCorporation'])
				// 	->name('api.moon-extractions.by-corporation')
				// 	->whereNumber('corporationId');
				
				// // Get extractions by system
				// Route::get('/system/{systemId}', [MoonExtractionsController::class, 'bySystem'])
				// 	->name('api.moon-extractions.by-system')
				// 	->whereNumber('systemId');
				
				// // Get specific extraction by structure ID
				// Route::get('/structure/{structureId}', [MoonExtractionsController::class, 'show'])
				// 	->name('api.moon-extractions.show')
				// 	->whereNumber('structureId');
            });
        });
    });
});
