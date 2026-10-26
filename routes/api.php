<?php

declare(strict_types=1);

use App\Http\Controllers\CreateProductController;
use App\Http\Controllers\ListCategoriesController;
use App\Http\Controllers\PublishProductController;
use App\Http\Controllers\SearchProductsController;
use App\Http\Controllers\ShowProductController;
use App\Http\Controllers\UpdateVariantController;
use Illuminate\Support\Facades\Route;

// Called only by the BFFs. Contract: shopwise-contracts/openapi/catalog.yaml.
// Storefront reads are public: they only ever expose published products and active variants.
Route::get('/categories', ListCategoriesController::class)->name('categories.index');
Route::get('/products', SearchProductsController::class)->name('products.index');
Route::get('/products/{slug}', ShowProductController::class)->name('products.show');

Route::middleware('auth.jwt:admin')->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/products', CreateProductController::class)->name('products.store');
    Route::post('/products/{product}/publish', PublishProductController::class)->name('products.publish');
    Route::patch('/variants/{variant}', UpdateVariantController::class)->name('variants.update');
});
