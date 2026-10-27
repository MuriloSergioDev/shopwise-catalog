<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ListAdminProductsRequest;
use App\Http\Resources\AdminProductSummaryResource;
use App\Queries\ListAdminProducts;
use Illuminate\Http\JsonResponse;

final class ListAdminProductsController
{
    public function __invoke(ListAdminProductsRequest $request, ListAdminProducts $listAdminProducts): JsonResponse
    {
        $products = $listAdminProducts->handle($request->toData());

        // { data, meta } with the contract's PageMeta, not Laravel's paginator links.
        return response()->json([
            'data' => AdminProductSummaryResource::collection($products->items())->resolve($request),
            'meta' => [
                'page' => $products->currentPage(),
                'perPage' => $products->perPage(),
                'total' => $products->total(),
                'lastPage' => $products->lastPage(),
            ],
        ]);
    }
}
