<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ChapterController;

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

Route::prefix('v1')->group(function () {
    Route::prefix('quran')->group(function () {
        // List all chapters
        Route::get('/chapters', [ChapterController::class, 'index']);

        // Search chapters - MUST come before {id} route
        Route::get('/chapters/search', [ChapterController::class, 'search']);

        // Get single chapter details
        Route::get('/chapters/{id}', [ChapterController::class, 'show']);

        // Get chapter info in specific language
        Route::get('/chapters/{id}/info', [ChapterController::class, 'info']);
    });
});

Route::get('/chapters/{id}/debug', [ChapterController::class, 'debug']);
