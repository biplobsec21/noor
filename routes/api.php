<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ChapterController;
use App\Http\Controllers\Api\V1\LanguageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::prefix('v1')->group(function () {
//     Route::prefix('quran')->group(function () {
//         // List all chapters
//         Route::get('/chapters', [ChapterController::class, 'index']);

//         // Search chapters - MUST come before {id} route
//         Route::get('/chapters/search', [ChapterController::class, 'search']);

//         // Get single chapter details
//         Route::get('/chapters/{id}', [ChapterController::class, 'show']);

//         // Get chapter info in specific language
//         Route::get('/chapters/{id}/info', [ChapterController::class, 'info']);
//     });
// });

// Route::get('/chapters/{id}/debug', [ChapterController::class, 'debug']);
/*
|--------------------------------------------------------------------------
| Quran API Routes
|--------------------------------------------------------------------------
|
| These routes handle all Quran chapter-related API endpoints.
| All routes automatically sync data from Quran Foundation API when needed.
|
*/

Route::prefix('v1/quran')->name('quran.')->group(function () {

    // Public Chapter Routes
    Route::controller(ChapterController::class)->group(function () {

        // Get all chapters (auto-syncs if DB is empty)
        // GET /api/v1/quran/chapters?language=en&paginate=true&per_page=20
        Route::get('/chapters', 'index')->name('chapters.index');

        // Get single chapter by ID (auto-syncs if not found)
        // GET /api/v1/quran/chapters/1?language=en
        Route::get('/chapters/{id}', 'show')
            ->where('id', '[0-9]+')
            ->name('chapters.show');

        // Get chapter info/metadata (auto-syncs if not found)
        // GET /api/v1/quran/chapters/1/info?language=en&locale=en_US
        Route::get('/chapters/{id}/info', 'info')
            ->where('id', '[0-9]+')
            ->name('chapters.info');

        // Search chapters by name
        // GET /api/v1/quran/chapters-search?q=faith&language=en
        Route::get('/chapters-search', 'search')->name('chapters.search');

        // Get chapters by revelation place (Makki/Madani)
        // GET /api/v1/quran/chapters/revelation/makkah?language=en
        Route::get('/chapters/revelation/{place}', 'byRevelationPlace')
            ->where('place', 'makkah|madinah')
            ->name('chapters.revelation-place');

        // Get chapters ordered by revelation order
        // GET /api/v1/quran/chapters/revelation-order?language=en
        Route::get('/chapters/revelation-order', 'byRevelationOrder')
            ->name('chapters.revelation-order');

        // Force refresh chapter from API
        // POST /api/v1/quran/chapters/1/refresh?language=en
        Route::post('/chapters/{id}/refresh', 'refresh')
            ->where('id', '[0-9]+')
            ->name('chapters.refresh');

        // Sync all chapters from API (admin endpoint - add auth middleware)
        // POST /api/v1/quran/chapters/sync?language=en
        Route::post('/chapters/sync', 'syncAll')
            // ->middleware('auth:sanctum', 'can:sync-chapters')
            ->name('chapters.sync');
    });

    // Languages routes (new)
    Route::prefix('languages')->group(function () {
        Route::get('/', [LanguageController::class, 'index']);
        Route::get('/search', [LanguageController::class, 'search']);
        Route::get('/direction/{direction}', [LanguageController::class, 'byDirection']);
        Route::get('/{isoCode}', [LanguageController::class, 'show']);
        Route::post('/sync', [LanguageController::class, 'sync']); // Admin only
    });
});

/*
|--------------------------------------------------------------------------
| Route Examples & Usage
|--------------------------------------------------------------------------
|
| Basic Usage:
| -----------
| GET /api/v1/quran/chapters                    - Get all chapters
| GET /api/v1/quran/chapters?language=ar        - Get chapters in Arabic
| GET /api/v1/quran/chapters?paginate=true      - Get paginated results
| GET /api/v1/quran/chapters/1                  - Get chapter 1 (Al-Fatihah)
| GET /api/v1/quran/chapters/1/info             - Get chapter 1 info
|
| Advanced Usage:
| --------------
| GET /api/v1/quran/chapters-search?q=faith                      - Search chapters
| GET /api/v1/quran/chapters/revelation/makkah                   - Get Makki chapters
| GET /api/v1/quran/chapters/revelation-order                    - Get by revelation order
| POST /api/v1/quran/chapters/1/refresh                          - Force API refresh
| POST /api/v1/quran/chapters/sync                               - Sync all from API
|
| With Multiple Parameters:
| ------------------------
| GET /api/v1/quran/chapters?language=ar&paginate=true&per_page=30
| GET /api/v1/quran/chapters/1/info?language=fr&locale=fr_FR
| GET /api/v1/quran/chapters/revelation/madinah?language=en
|
| Using Route Names:
| -----------------
| route('quran.chapters.index')                  - /api/v1/quran/chapters
| route('quran.chapters.show', ['id' => 1])      - /api/v1/quran/chapters/1
| route('quran.chapters.info', ['id' => 1])      - /api/v1/quran/chapters/1/info
|
*/
