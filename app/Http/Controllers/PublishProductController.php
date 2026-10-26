<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PublishProduct;
use App\Http\Resources\ProductResource;
use App\Models\Product;

final class PublishProductController
{
    public function __invoke(Product $product, PublishProduct $publishProduct): ProductResource
    {
        return ProductResource::make($publishProduct->handle($product));
    }
}
