<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;

/** Any status, every variant (inactive ones too): the admin's view of a product. */
final class ShowAdminProductController
{
    public function __invoke(Product $product): ProductResource
    {
        return ProductResource::make($product->load(['category', 'variants.options']));
    }
}
