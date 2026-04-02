<?php

use App\Http\Controllers\AnalysisReportController;
use App\Http\Controllers\AuthManager;
use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\FinancialEstimationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\CommerceRegisterController;

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
Route::get('commerce-registers/samples', [CommerceRegisterController::class, 'samples']);
Route::middleware('auth:sanctum')->post('/user/fcm-token', [AuthManager::class, 'updateFcmToken']);


Route::post('/registration',[AuthManager::class,'registrationpost']);
Route::post('/login',[AuthManager::class,'loginpost']);
Route::middleware('auth:sanctum')->group(function () {
Route::put('/user/update', [AuthManager::class, 'updateProfile']);
Route::delete('/user/delete', [AuthManager::class, 'deleteAccount']);
Route::get('/user/me', [AuthManager::class, 'getMyUser']);
Route::post('/logout', [AuthManager::class, 'logout']);


});



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/idea/reanalyze', [IdeaController::class, 'reanalyze']);
    Route::post('/ideas', [IdeaController::class, 'store']);
    Route::get('/ideas/my', [IdeaController::class, 'myIdeas']);
    Route::get('/ideas/{id}', [IdeaController::class, 'show']);
    Route::post('/idea/update', [IdeaController::class, 'update']);
    Route::post('/idea/delete', [IdeaController::class, 'delete']);
    Route::post('/ideas/region-analysis',[IdeaController::class, 'runRegionAnalysis']);
    Route::post('/ideas/property-price', [IdeaController::class, 'predictPropertyPrice']);
});
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/competitors/get', [CompetitorController::class, 'get']);
    Route::post('/recommendations/get', [RecommendationController::class, 'get']);
    Route::post('/financial/get', [FinancialEstimationController::class, 'get']);
    Route::post('/report/get', [AnalysisReportController::class, 'get']);

});
