<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\ProductSearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response of GET /api/products: { data, meta, facets } as in shopwise-contracts/openapi/catalog.yaml.
 *
 * @property ProductSearchResult $resource
 */
final class ProductSearchResource extends JsonResource
{
    /** The response already has its own top-level "data". */
    public static $wrap;

    /**
     * @return array{data: AnonymousResourceCollection, meta: array{page: int, perPage: int, total: int, lastPage: int}, facets: array{categories: list<array{slug: string, name: string, count: int}>, options: list<array{name: string, values: list<array{value: string, count: int}>}>}}
     */
    public function toArray(Request $request): array
    {
        $products = $this->resource->products;

        return [
            'data' => ProductSummaryResource::collection($products->getCollection()),
            'meta' => [
                'page' => $products->currentPage(),
                'perPage' => $products->perPage(),
                'total' => $products->total(),
                'lastPage' => $products->lastPage(),
            ],
            'facets' => [
                'categories' => $this->resource->categoryFacets,
                'options' => $this->resource->optionFacets,
            ],
        ];
    }
}
