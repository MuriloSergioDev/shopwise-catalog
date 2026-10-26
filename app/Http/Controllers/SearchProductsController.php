<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchProductsRequest;
use App\Http\Resources\ProductSearchResource;
use App\Queries\SearchProducts;

final class SearchProductsController
{
    public function __invoke(SearchProductsRequest $request, SearchProducts $searchProducts): ProductSearchResource
    {
        return ProductSearchResource::make($searchProducts->handle($request->toData()));
    }
}
