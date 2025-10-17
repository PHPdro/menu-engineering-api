<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\IngredientPriceController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DishController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeItemController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\AnalyticsController;

Route::apiResource('ingredients', IngredientController::class);
Route::apiResource('suppliers', SupplierController::class);
Route::apiResource('ingredient-prices', IngredientPriceController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('batches', BatchController::class);
Route::apiResource('dishes', DishController::class);
Route::apiResource('recipes', RecipeController::class);
Route::apiResource('recipe-items', RecipeItemController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('sales', SaleController::class)->only(['index','store','show']);

// Analytics & alerts
Route::get('analytics/menu-matrix', [AnalyticsController::class, 'menuMatrix']);
Route::get('analytics/perishables-alerts', [AnalyticsController::class, 'perishablesAlerts']);
Route::get('analytics/price-trends', [AnalyticsController::class, 'priceTrends']);
Route::get('analytics/traffic-flow', [AnalyticsController::class, 'trafficFlow']);
Route::get('analytics/breakeven', [AnalyticsController::class, 'breakeven']);


