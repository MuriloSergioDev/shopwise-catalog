<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\NewProductData;
use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Shopwise\Contracts\Events\Catalog\VariantChangedV1;
use Shopwise\Kit\Messaging\Outbox;

final readonly class CreateProduct
{
    /**
     * Creates a draft. Every variant is announced right away so inventory and pricing can stock and price
     * the SKU before the product goes live.
     */
    public function handle(NewProductData $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::query()->create([
                'category_id' => $data->categoryId,
                'slug' => $data->slug,
                'name' => $data->name,
                'description' => $data->description,
                'images' => $data->images,
                'status' => ProductStatus::Draft,
            ]);

            foreach ($data->variants as $variantData) {
                $variant = $product->variants()->create(['sku' => $variantData->sku, 'name' => $variantData->name, 'active' => true]);
                $variant->replaceOptions($variantData->options);

                Outbox::record(VariantChangedV1::TYPE, $product->id, $variant->changedEvent()->toArray(), VariantChangedV1::SCHEMA);
            }

            return $product->load(['category', 'variants.options']);
        });
    }
}
