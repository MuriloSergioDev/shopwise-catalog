<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ProductSearchResult
{
    /**
     * @param  LengthAwarePaginator<int, Product>  $products
     * @param  list<array{slug: string, name: string, count: int}>  $categoryFacets
     * @param  list<array{name: string, values: list<array{value: string, count: int}>}>  $optionFacets
     */
    public function __construct(
        public LengthAwarePaginator $products,
        public array $categoryFacets,
        public array $optionFacets,
    ) {}
}
