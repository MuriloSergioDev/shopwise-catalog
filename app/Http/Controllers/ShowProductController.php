<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShowProductController
{
    public function __invoke(string $slug): ProductResource
    {
        $product = Product::query()->published()->where('slug', $slug)
            ->with(['category', 'variants' => fn (HasMany $variants) => $variants->where('active', true), 'variants.options'])
            ->firstOrFail();

        return ProductResource::make($product);
    }
}
